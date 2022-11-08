<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\State;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Workflow;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Admin\Admin\Entity as Admin;

class Core extends Base\Core
{
    public function create(array $input)
    {
        //
        // We will need workflow ID and current level of the action in context.
        // So we get the action first and then fetch the others.
        //
        /** @var Action\Entity $action */
        $action = $this->repo->workflow_action->findOrFailPublic(
            $input[Entity::ACTION_ID]);

        if ($action->getState() !== State\Name::OPEN)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_IN_OPEN_STATES);
        }

        $permissionName = $action->permission->getName();

        $isAdminAttemptingPayoutReject = false;

        /** @var BasicAuth $basicAuth */
        $basicAuth = $this->app['basicauth'];

        //
        // If the permission being worked on is a merchant side permission (ex: RazorpayX Workflows)
        // the checkerEntity is the current user, else admin.
        //
        $checkerEntity = $basicAuth->getAdmin();
        $checkerType   = 'admin';

        if (Permission\Name::isMerchantPermission($permissionName) === true)
        {
            if (($checkerEntity === null) or
                (in_array(Permission\Name::REJECT_PAYOUT_BULK, $checkerEntity->getPermissionsList()) === false))
            {
                $checkerEntity = $basicAuth->getUser();
                $checkerType   = 'user';
            }
            else
            {
                $isAdminAttemptingPayoutReject = true;
            }
        }

        if ($checkerEntity === null)
        {
            throw new Exception\LogicException('Checker null, unexpected', null, ['input' => $input]);
        }

        $this->trace->info(TraceCode::WORKFLOW_MAKER_AND_CHECKER, [
            Action\Entity::ACTION_ID            => $action->getPublicId(),
            Action\Entity::MAKER                => $action->getMakerId(),
            Action\Entity::MAKER_TYPE           => $action->getMakerType(),
            Action\Entity::PERMISSION_ID        => $action->permission->getId(),
            Action\Entity::PERMISSION_NAME      => $permissionName,
            Action\Entity::WORKFLOW_ID          => $action->getWorkflowId(),
            Workflow\Constants::WORKFLOW_NAME   => $action->workflow->getName(),
            Action\Checker\Entity::CHECKER_TYPE => $checkerType,
            Action\Checker\Entity::CHECKER_ID   => $checkerEntity->getId(),
            "validation_result"                 => (new Action\Validator)->validateMakerIsNotCheckerOrOwner($action, $checkerEntity, $checkerType)
        ]);


        //
        // When a checker request is made, we need to first
        // verify whether the admin/user can check the action
        // as well as whether we need any more approvals
        // for the action at the current level or not.
        //

        // Get checker roles
        if ($checkerType === 'admin')
        {
            $checkerRoleIds = $checkerEntity->roles()->allRelatedIds()->toArray();
        }
        else
        {
            // If the entity is a user(which implies the product is banking),
            // then the role id for that user for the merchant in context
            // will have to be fetched from the merchant_users table.
            // This is because the role_map table doesn't have any merchant context.
            $checkerRoleIds = (new User\Core())->getUserRoleIdInMerchantForWorkflow($checkerEntity->getId());
        }

        $currentLevel = $action->getCurrentLevel();

        $workflowId = $action->workflow->getId();
        // A superadmin should be able to execute any open workflow bypassing all the steps
        if (($checkerType === 'admin') and ($checkerEntity->isSuperAdmin() === true))
        {
            $this->repo->transactionOnLiveAndTest(function() use ($action, $checkerEntity, $input, $permissionName)
            {
                // State change if checker rejected
                if ($input[Entity::APPROVED] == 1)
                {
                    (new Action\Core)->approveActionForcefully($action, $checkerEntity);

                    $this->executeAction($action, $checkerEntity->getSuperAdminRole(), $checkerEntity);
                }
                else
                {
                    (new Action\Core)->applyActionRejectionStateChanges(
                        $action,
                        $checkerEntity,
                        $checkerEntity->getSuperAdminRole());

                    $this->notifyOnReject($action, $permissionName);
                }
            });

            return null;
        }

        //
        // Ideally $steps should have only 1 row when searched by
        // level, workflow ID and role IDs. Sure there could be multiple
        // steps in the same level and all the roles may belong to the
        // current checker in context that will lead to multiple $steps.
        //
        // If Admin is rejecting a payout there are no relevant roles in
        // workflow, hence we will fetch all open steps, so that admin
        // can reject one of them
        if ($isAdminAttemptingPayoutReject)
        {
            $steps = $this->repo
                ->workflow_step
                ->findByLevelAndWorkflowId($currentLevel, $workflowId);
        }
        else
        {
            $steps = $this->repo
                ->workflow_step
                ->findByLevelWorkflowIdAndRoleId($currentLevel, $workflowId, $checkerRoleIds);
        }

        $checkNotRequired = false;

        //
        // If the checker in context need not perform any check
        // because the current steps does not require any check from any
        // of his roles then just set a flag and exit.
        //
        if ($steps->count() === 0)
        {
            $checkNotRequired = true;
        }

        //
        // There may be multiple steps for the current checker's roles
        // in the current level. We just need to check if any of them
        // requires a check. If yes then we go ahead otherwise
        // fail with an exception.
        //
        // We let the checker proceed irrespective of the $opType (OR or AND) because
        // after every check `updateCurrentLevelIfNeeded` (below) goes through
        // the $opType logic, etc. and updates the current level (to next one) anyway.
        //
        // Let's take an example of 2 steps (same level, same workflow_id) where
        // R1 and R2 are required to commit 1 and 2 checks respectively with $opType = or.
        // Also both of them already got 1 check each.
        // Now if we consider the for loop flow below then it will
        // continue checking the current level (both steps) because R2 requires 1 more check.
        // But this *won't* happen because when R1 had been checked earlier
        // `updateCurrentLevelIfNeeded` below would have already updated the level
        // or even auto-approved/executed the workflow.
        //
        // This also means that if the $opType is or then right after R1 check
        // the level would have been updated and the other step would never
        // come into consideration because the same level will never again execute.
        //
        /** @var Workflow\Step\Entity $step */
        foreach ($steps as $step)
        {
            $stepId    = $step->getId();
            $actionId  = $action->getId();
            $checkerId = $checkerEntity->getId();

            $alreadyReviewed = $this->repo
                                    ->action_checker
                                    ->hasCheckerAlreadyReviewedActionForStep($checkerId, $actionId, $stepId);

            if ($alreadyReviewed === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL,
                    null,
                    ['step_id' => $stepId, 'action_id' => $actionId, 'checker_id' => $checkerId]);
            }

            //
            // Check if $step requires any check by matching
            // workflow_step.reviewer_count with count(action_checkers)
            //
            $requiredReviews = $step->getReviewerCount();

            $reviewsDone = $this->repo
                                ->action_checker
                                ->fetchCountByActionIdForStep($actionId, $stepId);

            if ($reviewsDone >= $requiredReviews)
            {
                $checkNotRequired = true;
            }
            else
            {
                $checkNotRequired = false;

                // Once we break from foreach $step will be the one
                // in current context right before break. $step in current
                // context will be used for further operations.
                break;
            }
        }

        if ($checkNotRequired === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL);
        }

        // Legacy, fix
        if ($checkerType === 'admin')
        {
            // ADMIN_ID is the checker's ID (current request's checker)
            $input[Entity::ADMIN_ID] = $checkerEntity->getId();
        }

        // Set the step for which checker is checking
        $input[Entity::STEP_ID] = $step->getId();

        // Create and save the action_checker Entity
        $checker = new Entity;

        $checker->generateId();

        $this->skipStrictValidationIfApplicable($checker, $permissionName);

        $checker->build($input);

        // Legacy, fix
        if ($checkerType === 'admin')
        {
            $checker->admin()->associate($checkerEntity);
        }

        $checker->checker()->associate($checkerEntity);

        $checker->action()->associate($action);

        $checker->step()->associate($step);

        $this->repo->transactionOnLiveAndTest(function() use ($action, $checker, $checkerEntity, $step, $isAdminAttemptingPayoutReject)
        {
            $this->repo->saveOrFail($checker);

            // State change if checker rejected
            if ($checker->isApproved() === false)
            {
                if ($isAdminAttemptingPayoutReject === true)
                {
                    try
                    {
                        (new Action\Core)->applyActionRejectionStateChanges($action, $checkerEntity, $checkerEntity->getPayoutRejectRole());
                    }
                    catch(\Throwable $throwable)
                    {
                        // Sometimes ES sync fails which results in action data not being
                        // entered in ES. In this case too, admin should be allowed to bypass
                        // this error, and reject the payout.
                        if ($throwable->getCode() !== ErrorCode::BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND)
                        {
                            throw $throwable;
                        }
                    }
                }
                else
                {
                    (new Action\Core)->applyActionRejectionStateChanges($action, $checkerEntity, $step->role);
                }
            }
            else
            {
                //
                // Once all the roles x reviewer_count have approved
                // an action, we need to update the level so that
                // we can show the action to next level/step checkers
                //
                (new Action\Core)->updateCurrentLevelIfNeeded($action);

                //
                // If all the checkers have approved then approve
                // and close the action. This will also update
                // action_state (state machine).
                //
                (new Action\Core)->checkAndMarkActionApproved($action, $checkerEntity);
            }
        });

        $this->executeAction($action, $step->role, $checkerEntity);

        $this->notifyOnReject($action, $permissionName);

        return $checker;
    }

    protected function executeAction(Action\Entity $action, Role $role, Base\PublicEntity $checkerEntity)
    {
        // Currently we can execute from both route and here, will remove route eventually.
        if ($action->getApproved() === true)
        {
            (new Action\Service)->executeAction($action->getPublicId(), $role, $checkerEntity);
        }
    }

    protected function notifyOnReject(Action\Entity $action, string $permissionName)
    {
        if ($action->isRejected() === true)
        {
            // get reject handler and call
            // if the reject handler fails,
            // the decision to retry or not will reside with the corresponding reject handler
            try
            {
                $rejectHandler = Action\Constants::getActionRejectHandlerByPermissionName($permissionName);

                if (is_null($rejectHandler) === false)
                {
                    (new $rejectHandler)->handleOnRejectWorkflowAction($action);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::WORKFLOW_ACTION_NOTIFY_REJECT_FAILED,
                    ['id' => $action->getId()]
                );
            }
        }
    }

    protected function skipStrictValidationIfApplicable(Entity $checker, string $permissionName)
    {
        if (in_array($permissionName, Constants::SKIP_CHECKER_STRICT_VALIDATION_FOR_PERMISSIONS) === true)
        {
            $checker->getValidator()->setStrictFalse();
        }
    }
}
