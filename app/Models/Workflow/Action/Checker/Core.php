<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Admin\Admin\Entity as Admin;

class Core extends Base\Core
{
    public function create(array $input)
    {
        // When a checker request is made, we need to first
        // verify whether the admin user can check the action
        // as well as whether we need any more approvals
        // for the action at the current level or not.

        $admin = $this->app['basicauth']->getAdmin();

        // Get checker roles
        $adminRoleIds = $admin->roles()->allRelatedIds()->toArray();

        // We will need workflow ID and current level
        // of the action in context. So get the action first and then
        // fetch the others.

        $action = $this->repo->workflow_action->findOrFailPublic(
            $input[Entity::ACTION_ID]);

        if ($action->getState() !== State\Name::OPEN)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_NOT_IN_OPEN_STATES);
        }

        // Get the workflow action's current level
        $currentLevel = $action->getCurrentLevel();

        $workflowId = $action->workflow->getId();

        // A superadmin should be able to execute any open
        // workflow bypassing all the steps
        if ($admin->isSuperAdmin() === true)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($action, $admin, $input)
            {
                // State change if checker rejected
                if ($input[Entity::APPROVED] == 1)
                {
                    (new Action\Core)->approveActionForcefully($action, $admin);

                    $this->executeAction($action, $admin->getSuperAdminRole());
                }
                else
                {
                    $this->applyActionRejectionStateChanges($action, $admin, $admin->getSuperAdminRole());
                }
            });

            return null;
        }

        // Ideally $steps should have only 1 row when searched by
        // level, workflow ID and role IDs. Sure there could be multiple
        // steps in the same level and all the roles may belong to the
        // current admin in context that will lead to multiple $steps.
        $steps = $this->repo->workflow_step
                            ->findByLevelWorkflowIdAndRoleId($currentLevel, $workflowId, $adminRoleIds);

        $checkNotRequired = false;

        // If the admin checker in context need not perform any check
        // because the current steps does not require any check from any
        // of his roles then just set a flag and exit.
        if ($steps->count() === 0)
        {
            $checkNotRequired = true;
        }

        // There may be multiple steps for the current admin's roles
        // in the current level. We just need to check if any of them
        // requires a check. If yes then we go ahead otherwise
        // fail with an exception.
        //
        // We let the checker proceed irrespective of the $opType (OR or AND) because
        // after every check `updateCurrentLevelIfNeeded` (below) goes through
        // the $opType logic, etc. and updates the current level anyway.
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
        foreach ($steps as $step)
        {
            // Check if $step requires any check by matching
            // workflow_step.reviewer_count with count(action_checkers)

            $requiredReviews = $step->getReviewerCount();

            $reviewsDone = $this->repo
                                ->action_checker
                                ->fetchCountByStep($step->getId());

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

        // ADMIN_ID is the checker's ID (current request's admin)
        $input[Entity::ADMIN_ID] = $admin->getId();

        // Set the step for which checker is checking
        $input[Entity::STEP_ID] = $step->getId();

        // Create and save the action_checker Entity
        $checker = new Entity;

        $checker->generateId();

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use ($action, $checker, $admin, $step)
        {
            $this->repo->saveOrFail($checker);

            // State change if checker rejected
            if ($checker->isApproved() === false)
            {
                $this->applyActionRejectionStateChanges($action, $admin, $step->role);
            }
            else
            {
                // Once all the roles x reviewer_count have approved
                // an action, we need to update the level so that
                // we can show the action to next level/step checkers
                (new Action\Core)->updateCurrentLevelIfNeeded($action);

                // If all the checkers have approved then approve
                // and close the action. This will also update
                // action_state (state machine).
                (new Action\Core)->checkAndMarkActionApproved($action, $admin);
            }
        });

        $this->executeAction($action, $step->role);

        return $checker;
    }

    protected function executeAction(Action\Entity $action, Role $role)
    {
        // Currently we can execute from both route and here, will remove route eventually.
        (new Action\Service)->executeAction($action->getPublicId(), $role);
    }

    /*
        State changes on rejection
    */
    protected function applyActionRejectionStateChanges($action, Admin $admin, Role $role)
    {
        $state = State\Name::REJECTED;

        $actionId = $action->getId();

        (new State\Core)->changeActionState($action, $state, $admin);

        (new Action\Core)->updateStateAndStateChanger($action, $state, $admin, $role);

        (new Differ\Core)->updateStateInEs($actionId, $state);
    }
}
