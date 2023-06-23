<?php

namespace RZP\Models\Workflow\Action;

use App;
use Route;
use Request;
use ReflectionMethod;

use RZP\Exception;
use RZP\Models\State;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use  RZP\Models\BankAccount;
use RZP\Models\Workflow\Helper;
use RZP\Models\Admin\Permission;
use RZP\Models\Base\PublicEntity;
use RZP\Models\BulkWorkflowAction;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Settlement\Service as SettlementService;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Models\Workflow\Observer\MerchantSelfServeObserver;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Notifications\Dashboard\Constants as MerchantNotificationsConstants;

use RZP\Models\Workflow;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Checker;

use RZP\Constants\Entity as E;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;


class Core extends Base\Core
{
    private function buildParams(array $input) : array
    {
        $maker = $this->app['workflow']->getWorkflowMaker();

        $orgId = $maker->getOrgId();

        $merchantId = null;

        $params = [
            Entity::ORG_ID      => $orgId
        ];

        $routePermission = $input[Differ\Entity::PERMISSION];

        // Implicit check for permission existance in the organisation.
        $permissionId = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg($routePermission, $orgId)
                             ->toArray()[0];
        //
        // For merchant app permissions, maker=merchant, we send the merchant ID for fetching
        // only workflows defined for the merchant
        //
        if (Permission\Name::isMerchantPermission($routePermission) === true)
        {
            $merchantId = $maker->getId();
        }

        // We don't need to check the following 2 things:
        //
        // - Whether a workflow exists against the routePermission
        // because this is already done in workflow middleware
        //
        // - Whether the maker has access to this permission because
        // that is also done in the middleware or should be done
        // from wherever this code is called/triggered.

        // Currently single permission can have only 1 workflow
        // App level checks are in place. But this is sort of progressive
        // code where a single permission might have multiple workflows
        // in future.
        $workflows = $this->getWorkflowsForPermission($routePermission, $orgId, $merchantId);

        // More than one workflow could be found.
        $workflow = $workflows->first();

        $params[Entity::PERMISSION_ID] = $permissionId;

        $params[Entity::DIFFER] = $input;

        // $params will also have ENTITY_ID and
        // ENTITY_NAME which will get saved in
        // workflow_actions table.
        $params[Entity::ENTITY_ID] = $input[Differ\Entity::ENTITY_ID] ?: null;

        // We can verify ID using one of the static functions in
        // PublicEntity by instantiation the Entity class of
        // $input[Differ\Entity::ENTITY_NAME] but we'll keep it
        // simple and fast for now.

        // explode('_', null) === [""]
        $params[Entity::ENTITY_ID] = last(explode('_', $params[Entity::ENTITY_ID])) ?: null;

        $params[Entity::ENTITY_NAME] = $input[Differ\Entity::ENTITY_NAME] ?: null;

        // Evaluate for workflow rules
        $evaluatedWorkflow = $this->evaluateWorkflowRulesIfDefined($input);

        //
        // Override $workflow with $evaluatedWorkflow if it's non-null
        // This means that a rule evaluation resulted in another workflow being
        // picked up.
        //
        $workflow = $evaluatedWorkflow ?: $workflow;

        $params[Entity::WORKFLOW_ID] = $workflow->getId();

        // TODO:: add code for actual verification of maker_type here
        $params[Entity::MAKER_TYPE] = $input[Entity::MAKER_TYPE] ?: null;

        $params[Entity::MAKER_ID] = $input[Entity::MAKER_ID] ?: null;

        return $params;
    }

    private function evaluateWorkflowRulesIfDefined(array $input)
    {
        $permission = $input[Differ\Entity::PERMISSION];

        //
        // Workflow rules only apply to the create_payout permission for now
        // Very custom, non-generic and ugly logic follows
        //
        if ($permission !== Permission\Name::CREATE_PAYOUT)
        {
            return null;
        }

        /** @var Merchant\Entity $merchant */
        $merchant = app('basicauth')->getMerchant();

        $this->trace->info(
            TraceCode::PAYOUT_WORKFLOW_EVALUATION_INPUT,
            ['input' => $input, 'merchant' => $merchant->getId()]);

        //
        // The amount attribute will definitely exist in the "new" attributes of the workflows diff
        // at this point. If it doesn't, Payout validators will fail before the code reaches the workflow
        // layer
        //
        $amount = $input[Differ\Entity::DIFF]['new']['amount'];

        $workflow = (new Workflow\PayoutAmountRules\Core)->fetchWorkflowForMerchantIfDefined($amount, $merchant);

        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_EVALUATION_RESULT, ['workflow_id' => optional($workflow)->getId()]);

        return $workflow;
    }

    /*
        In case of a retry we won't need to do any parsing/processing
        that we do in `buildParams()` since that was already done
        in the initial call (before exception was thrown by workflow trigger)
        and now we have the final values to insert in the RDBMS directly.
    */
    private function buildParamsForRetry(array $input) : array
    {
        $strip = 'verifyIdAndStripSign';

        $makerClass = E::getEntityClass($input[Entity::MAKER_TYPE]);

        $params = [
            Entity::ORG_ID          => Org\Entity::$strip($input[Entity::ORG_ID]),
            Entity::MAKER_ID        => $makerClass::$strip($input[Entity::MAKER_ID]),
            Entity::MAKER_TYPE      => $input[Entity::MAKER_TYPE],
            Entity::WORKFLOW_ID     => Workflow\Entity::$strip($input[Entity::WORKFLOW_ID]),
            Entity::PERMISSION_ID   => Permission\Entity::$strip($input[Entity::PERMISSION_ID]),
            Entity::ENTITY_ID       => $input[Entity::ENTITY_ID],
            Entity::ENTITY_NAME     => $input[Entity::ENTITY_NAME],
        ];

        return $params;
    }

    /*
        When the action is created for the first time
        $retry will/should be false. But at times the workflow
        trigger code will be inside a transaction of the main
        login. For instance the code for "assigning schedule"
        is a good example. There the workflow triggers inside
        a transaction which rolls back if the workflow
        is supposed to throw an exception to end the runtime
        execution and throw the workflow action as the response.

        Although the workflow action object will be thrown as
        the response (because in-memory) since the transaction
        rollsback the transaction/code to create workflow action
        (which is the the following create function) will also fail.
        Hence no entries will be made into the DB.

        To prevent that when the exception is caught in Exception/Handler.php
        we'll trigger `retryCreate` from there which will call `create`
        with $retry = true. This will help build parameters to be inserted
        into the DB accordingly. What this means is we'll take the in-memory
        response data and insert that data in the DB directly without any
        processing which we did in the initial create call.

        Similarly basis the same $retry flag we can prevent further inserts
        into ES or any similar caching/DB system that is separate from
        our main RDBMS and to whom the entries did not fail because
        transaction rollbacks don't affect that.
    */
    public function create(array $input, $retry = false, PublicEntity $maker): Entity
    {
        $action = new Entity;

        $action->generateId();

        if ($retry === true)
        {
            $params = $this->buildParamsForRetry($input);

            $actionId = $input[Entity::ID];

            Entity::verifyIdAndStripSign($actionId);

            $action->setId($actionId);
        }
        else
        {
            $input[Entity::MAKER_TYPE] = $this->app['workflow']->getWorkflowMakerType();

            $input[Entity::MAKER_ID] = $maker->getId();

            $params = $this->buildParams($input);
        }

        $params[Entity::TAGS] = $input[Entity::TAGS] ?? [];

        // $params has data for Action\Entity (Mysql) + Differ\Entity (ES)

        $this->repo->transactionOnLiveAndTest(function() use ($action, $params, $retry, $maker)
        {
            $differInput = $params[Entity::DIFFER] ?? null;

            $tags = $params[Entity::TAGS] ?? [];

            unset($params[Entity::TAGS]);
            unset($params[Entity::DIFFER]);

            $action->build($params);

            $workflow = $this->repo->workflow->findOrFailPublic($params[Entity::WORKFLOW_ID]);

            $permission = $this->repo->permission->findOrFailPublic($params[Entity::PERMISSION_ID]);

            $org = $this->repo->org->findOrFailPublic($params[Entity::ORG_ID]);

            $action->maker()->associate($maker);

            $action->workflow()->associate($workflow);

            $action->permission()->associate($permission);

            $action->org()->associate($org);

            $action->tag($tags);

            $this->repo->saveOrFail($action);

            $this->createInitialStateForAction($action, $maker);

            if (($retry === false) and (empty($differInput) === false))
            {
                unset($differInput[Entity::ORG_ID]);
                // Create the diff for the entity
                (new Differ\Core)->create($action, $differInput);
            }
        });

        return $action;
    }

    protected function createInitialStateForAction(Entity $action, PublicEntity $maker)
    {
        $input = [
            State\Entity::NAME       => State\Name::OPEN,
        ];

        $actionState = (new State\Core)->createForMakerAndEntity($input, $maker, $action);

        return $actionState;
    }

    /**
     * Fetch workflows mapped to the permissions for this organisation.
     * This checks for if the permission is present for the organisation
     * and if a workflow is mapped against the permission.
     *
     * @param string      $permission
     * @param string      $orgId
     * @param string|null $merchantId
     *
     * @return array
     */
    public function getWorkflowsForPermission(string $permission, string $orgId, string $merchantId = null)
    {
        $permissionId = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg($permission, $orgId)
                             ->first();

        $permissionId = $permissionId ?: '';

        // Implicit check for workflow in the organisation against permission ids.
        $workflows = $this->repo
                          ->workflow
                          ->fetchWorkflowsByPermissionsOrgAndMerchant(
                              $permissionId,
                              $orgId,
                              $merchantId,
                              [Workflow\Entity::PAYOUT_AMOUNT_RULE]);

        return $workflows;
    }

    /**
     * This function has to run in a transaction
     *
     * @param Entity       $action
     * @param PublicEntity $checkerEntity
     *
     * @return boolean
     */
    public function checkAndMarkActionApproved(Entity $action, PublicEntity $checkerEntity)
    {
        // 1. If action is already approved then return

        if ($action->getApproved() === true)
        {
            return true;
        }

        // 2. If current level is not the last level then return

        $workflowId = $action->getWorkflowId();

        $lastLevel = $this->repo->workflow_step
                                ->getLastLevelOfWorkflow($workflowId);

        if ($lastLevel !== $action->getCurrentLevel())
        {
            return false;
        }

        // 3. If the current level is approved (and it is already the last level)

        if ($this->isCurrentLevelApproved($action) === true)
        {
            $this->approveAction($action, $checkerEntity);
        }

        return true;
    }

    public function approveActionForcefully(Entity $action, Admin\Entity $checkerEntity)
    {
        if ($action->getApproved() === true)
        {
            return true;
        }

        $this->approveAction($action, $checkerEntity);

        return true;
    }

    protected function approveAction(Entity $action, PublicEntity $checkerEntity)
    {
        //
        // Set the action as approved and create a state change that it has
        // been moved to approved.
        //
        $this->repo->transactionOnLiveAndTest(function() use ($action, $checkerEntity)
        {
            $data = [
                Entity::APPROVED => true,
                Entity::STATE    => State\Name::APPROVED,
            ];

            $action->edit($data);

            $this->repo->saveOrFail($action);

            $stateData = [
                State\Entity::NAME      => State\Name::APPROVED,
            ];

            (new State\Core)->createForMakerAndEntity($stateData, $checkerEntity, $action);

            (new Differ\Core)->updateStateInEs(
                $action->getId(), $stateData[State\Entity::NAME]);
        });

        return $action;
    }

    protected function isCurrentLevelApproved(Entity $action)
    {
        $level = $action->getCurrentLevel();

        $workflowId = $action->getWorkflowId();

        $steps = $this->repo
                      ->workflow_step
                      ->findByLevelAndWorkflowId($level, $workflowId);

        // Get the op type (AND or OR)
        $opType = $steps[0]->getOpType();

        // All the step IDs for the current action's current level
        $stepIds = [];

        // Hashmap of all stepIds => required_review_count
        $stepReviewCountMap = [];

        foreach ($steps as $step)
        {
            $stepId = $step->getId();

            $stepReviewCountMap[$stepId] = $step->getReviewerCount();

            $stepIds[] = $stepId;
        }

        // Get total number of people who have approved (checked) this action
        $totalCheckerApprovals = $this->repo
                                      ->action_checker
                                      ->fetchApprovedCountByActionIdAndStepIds(
                                          $action->getId(), $stepIds);

        // Hashmap of stepIds => total_approvals_received
        $stepCheckerMap = [];

        foreach ($totalCheckerApprovals as $approval)
        {
            $stepId = $approval[Checker\Entity::STEP_ID];

            $stepCheckerMap[$stepId] = $approval['total'];
        }

        // So effectively now we have:
        // - $stepReviewCountMap - stores stepIds => required_review_count for all steps
        // in the current level.
        // - $stepCheckerMap - stores stepIds => total_approvals_received for all steps
        // in the current level

        // We just need to create 1 more map that will store whether the total approval count
        // for each step has reached or not.
        //
        // Hashmap of stepIds => true/false denoting whether any further approvals
        // are required or not.
        $stepApprovedMap = [];

        foreach ($stepReviewCountMap as $stepId => $reviewCount)
        {
            $approvalCount = $stepCheckerMap[$stepId] ?? 0;

            $stepApprovedMap[$stepId] = ($approvalCount === $reviewCount);
        }

        // Now all we need to do is for:
        // AND op - None of the stepId is false. Means all the required === received is true.
        // OR op - At least one stepId is true. Means at least one required === received is true.

        $levelApproved = false;

        if ($opType === Step\Entity::OP_TYPE_AND)
        {
            // If any of the check fails, level is not approved.
            $levelApproved = (in_array(false, $stepApprovedMap, true) === false);
        }
        else if ($opType === Step\Entity::OP_TYPE_OR)
        {
            // If any of the check passed, level is approved.
            $levelApproved = in_array(true, $stepApprovedMap, true);
        }

        return $levelApproved;
    }

    /**
     * This function has to be run in a transaction
     *
     * @param Entity $action
     */
    public function updateCurrentLevelIfNeeded(Entity $action)
    {
        // Get the total reviewer_count required across all
        // the roles (all the workflow_step entries)
        // for the current level of the action

        // Only open actions are supported
        if ($action->getState() !== State\Name::OPEN)
        {
            return;
        }

        $level = $action->getCurrentLevel();

        $workflowId = $action->getWorkflowId();

        $levelApproved = $this->isCurrentLevelApproved($action);

        // Check if there is any level (or step basically)
        // after workflow_actions.current_level
        $nextLevelStep = $this->repo
                              ->workflow_step
                              ->getNextLevelOfWorkflowId($level, $workflowId);

        // Finally if there's a next level AND
        // total approvals received is more than
        // total reviewer count (approvals) required then
        // update the level of the action.
        if ((empty($nextLevelStep) === false) and
            ($levelApproved === true))
        {
            $action->setCurrentLevel($nextLevelStep->getLevel());

            $this->repo->saveOrFail($action);
        }
    }

    public function get(string $id)
    {
        return $this->repo->workflow_action->findOrFailPublic($id);
    }

    public function getByIdAndOrgId(string $id, string $orgId)
    {
        Entity::verifyIdAndStripSign($id);

        return $this->repo->workflow_action->findByIdAndOrgId($id, $orgId);
    }

    public function edit(Entity $action, array $input)
    {
        //
        // Dashboard requirement is that we should not
        // let admin edit action if the action is closed.
        // Shift the check to Service.php if the check is
        // a blocker for other functionality
        //

        if(isset($input[Constants::WORKFLOW_TAGS])) {
            $action->tag($input[Constants::WORKFLOW_TAGS]);
            unset($input[Constants::WORKFLOW_TAGS]);
        }

        $action->getValidator()->validateActionIsOpen($action);

        $action->edit($input);

        if (isset($input[Entity::STATE_CHANGER_ID]) === true)
        {
            // Type can be user or admin
            $stateChangerType = $input[Entity::STATE_CHANGER_TYPE];

            $stateChanger = $this->repo->$stateChangerType->findOrFailPublic($input[Entity::STATE_CHANGER_ID]);

            $action->stateChanger()->associate($stateChanger);
        }

        if (isset($input[Entity::STATE_CHANGER_ROLE_ID]) === true)
        {
            $stateChangerRole = $this->repo->role->findOrFailPublic($input[Entity::STATE_CHANGER_ROLE_ID]);

            $action->stateChangerRole()->associate($stateChangerRole);
        }

        if (isset($input[Entity::OWNER_ID]) === true)
        {
            if (empty($action->getOwnerId()) === true)
            {
                $admin = $this->repo->admin->findOrFailPublic($input[Entity::OWNER_ID]);

                $this->trace->info(TraceCode::WORKFLOW_MAKER_AND_OWNER, [
                    Entity::ACTION_ID                 => $action->getPublicId(),
                    Entity::MAKER                     => $action->getMakerId(),
                    Entity::MAKER_TYPE                => $action->getMakerType(),
                    Entity::OWNER_ID                  => $input[Entity::OWNER_ID],
                    Entity::PERMISSION_ID             => $action->permission->getId(),
                    Entity::PERMISSION_NAME           => $action->permission->getName(),
                    Entity::WORKFLOW_ID               => $action->getWorkflowId(),
                    Workflow\Constants::WORKFLOW_NAME => $action->workflow->getName(),
                    "validation_result"  =>  $action->getValidator()->validateMakerIsNotCheckerOrOwner($action, $admin)
                ]);

                $action->owner()->associate($admin);
                $action->setAssignedAt();
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    Entity::OWNER_ID. ' already present.');
            }
        }

        $this->repo->saveOrFail($action);

        return $action;
    }

    public function close(Entity $action, $maker, $autoclose = false)
    {
        $action->getValidator()->validateCloseAction($maker, $autoclose);

        $this->repo->transactionOnLiveAndTest(function () use($action, $maker){

            $state = State\Name::CLOSED;

            $stateData = [
                State\Entity::NAME      => $state,
            ];

            if ($maker->isSuperAdmin() === true)
            {
                $this->updateStateAndStateChanger($action, $state, $maker, $maker->getSuperAdminRole());
            }
            else
            {
                $this->updateStateAndStateChanger($action, $state, $maker, null);
            }

            (new State\Core)->createForMakerAndEntity($stateData, $maker, $action);

            $documents = (new Differ\Core)->getDocumentsFromEs($action->getId());

            //
            // Sometimes Es sync fails , in this case support team should be able to
            // close workflow and recreate a new workflow
            //
            if (empty($documents) === false)
            {
                (new Differ\Core)->updateStateInEs(
                    $action->getId(), $stateData[State\Entity::NAME]);
            }
        });
    }

    /**
     * Handles state changes on rejection
     *
     * @param Entity       $action
     * @param PublicEntity $checkerEntity
     * @param Role\Entity  $role
     *
     * @throws Exception\BadRequestException
     */
    public function applyActionRejectionStateChanges(Entity $action, PublicEntity $checkerEntity, Role\Entity $role = null)
    {
        $state = State\Name::REJECTED;

        $actionId = $action->getId();

        (new State\Core)->changeActionState($action, $state, $checkerEntity);

        $this->updateStateAndStateChanger($action, $state, $checkerEntity, $role);

        (new Differ\Core)->updateStateInEs($actionId, $state);
    }

    public function updateState(Entity $action, string $state)
    {
        $input = [
            Entity::STATE => $state,
        ];

        return $this->edit($action, $input);
    }

    /**
     * This function will now be used instead of updateState so as to
     * store the information about the person(admin_id/user_id, and role_id) who
     * was responsible of actually executing the workflow. In case it is a
     * superadmin, then we allow to skip any steps and execute the workflow
     * forcefully. Hence the information about StateChanger. StateChanger
     * information will also be stored in case the workflow was closed or rejected.
     *
     * @param Entity           $action
     * @param string           $state
     * @param PublicEntity     $checkerEntity
     * @param Role\Entity|null $role
     *
     * @return Entity
     */
    public function updateStateAndStateChanger(
        Entity $action,
        string $state,
        PublicEntity $checkerEntity,
        Role\Entity $role = null
    )
    {
        $input = [
            Entity::STATE                 => $state,
            Entity::STATE_CHANGER_ID      => $checkerEntity->getId(),
            Entity::STATE_CHANGER_TYPE    => $checkerEntity->getEntity(),
            Entity::STATE_CHANGER_ROLE_ID => $role ? $role->getId() : null
        ];

        return $this->edit($action, $input);
    }

    public function initAuthDetails(array $authDetails)
    {
        if (empty($authDetails['merchant_id']) === false)
        {
            $merchantId = $authDetails['merchant_id'];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $this->app['basicauth']->setMerchant($merchant);
        }
    }

    public function fetchOpenActionOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionName,
        string $orgId = null)
    {
        $permissionId = $this->fetchPermissionId($permissionName, $orgId);

        $actions = $this->repo
                        ->workflow_action
                        ->getOpenActionOnEntityOperation($entityId, $entityName, $permissionId);

        return $actions;
    }

    public function fetchApprovedActionOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionName,
        string $orgId = null)
    {
        $permissionId = $this->fetchPermissionId($permissionName, $orgId);

        $actions = $this->repo
            ->workflow_action
            ->getApprovedActionOnEntityOperation($entityId, $entityName, $permissionId);

        return $actions;
    }

    public function fetchOpenActionOnEntityOperationWithPermissionList(
        string $entityId,
        string $entityName,
        array $permissionList,
        string $orgId = null)
    {
        $permissionIdList = $this->fetchPermissionListId($permissionList, $orgId);

        $actions = $this->repo
            ->workflow_action
            ->getOpenActionOnEntityOperationWithPermissionList($entityId, $entityName, $permissionIdList);

        return $actions;
    }

    public function fetchOpenActionOnEntityListOperation(
        array $entityIdList,
        string $entityName,
        string $permissionName)
    {
        $permissionIdList = $this->repo
            ->permission
            ->retrieveIdsByNames([$permissionName])
            ->toArray();

        if (empty($permissionIdList) === true)
        {
            throw new
            Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PERMISSION);
        }
        $permissionId = $permissionIdList[0]['id'];

        $actions = $this->repo
            ->workflow_action
            ->getOpenActionOnEntityListOperation($entityIdList, $entityName, $permissionId);

        return $actions;
    }

    public function fetchActionsOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionName,
        string $orgId = null)
    {
        $permissionId = $this->fetchPermissionId($permissionName, $orgId);

        $actions = $this->repo
            ->workflow_action
            ->fetchWorkflowAction($entityId, $entityName, $permissionId);

        return $actions;
    }

    private function fetchPermissionId(string $permissionName, $orgId)
    {
        $orgId = $orgId ?: $this->app['basicauth']->getOrgId();

        if ($orgId == null)
        {
            $maker = $this->app['workflow']->getWorkflowMaker();

            $orgId = $maker->getOrgId();
        }

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $permissionIdList = $this->repo
                            ->permission
                            ->retrieveIdsByNamesAndOrg($permissionName, $orgId)
                            ->toArray();

        if(empty($permissionIdList) === true)
        {
            throw new
            Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PERMISSION);
        }
        $permissionId = $permissionIdList[0];

        return $permissionId;
    }

    private function fetchPermissionListId(array $permissionList, $orgId)
    {
        $orgId = ($orgId ?: $this->app['basicauth']->getOrgId()) ?: $this->app['basicauth']->getMerchant()->getOrgId();

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $permissionIdList = $this->repo
            ->permission
            ->retrieveIdsByNamesAndOrgWithPermissionList($permissionList, $orgId)
            ->toArray();

        if (empty($permissionIdList) === true)
        {
            return [];
        }

        return array_unique($permissionIdList);
    }

    public function fetchActionStatus(
        string $entityId,
        string $entityName,
        string $permissionName,
        string $orgId = null)
    {
        $actions = $this->fetchActionsOnEntityOperation($entityId, $entityName, $permissionName, $orgId);

        $actions = $actions->toArrayPublic();

        $action = end($actions['items']);

       if ($action === false)
       {
           return $action;
       }

       return $action['state'];
    }

    public function executeAction($action, PublicEntity $checkerEntity, Role\Entity $role = null)
    {
        list($stateCore, $differCore) = [
            new State\Core,
            new Differ\Core,
        ];

        $diff = (new Differ\Service)->fetchRequest($action->getId());

        $routeParams = $diff[Differ\Entity::ROUTE_PARAMS];

        $payload = $diff[Differ\Entity::PAYLOAD];

        $controller = $diff[Differ\Entity::CONTROLLER];

        $functionName = $diff[Differ\Entity::FUNCTION_NAME];

        $authDetails = $diff[Differ\Entity::AUTH_DETAILS];

        $permissionName = $action->permission->getName();

        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ["before change payload...." => $payload]);

        $payload = $this->performMultipleWorkflowChanges($payload, $permissionName);

        /*
          * Decrypt the keys like password replaying the request
          *
          * Encryption place : app/Services/Workflow/Service.php encryptFields
         */
        $payload = (new Helper())->decryptSensitiveFieldsBeforeReplayingRequest($payload);

        // Replace the current request's payload with the
        // actual maker request payload.
        Request::replace($payload);

        // Create controller object
        $controller = App::make($controller);

        // Auth details have to be initialized before
        // the actual code (Controller@action) runs.
        $this->initAuthDetails($authDetails);

        $state = State\Name::EXECUTED;

        //
        // Should the original request be replayed?
        // If yes, the original payload is passed to the controller action
        //
        $replayOriginalRequest = true;

        //
        // In some circumstances (like create_payout), we have custom logic on how to process
        // workflow action execution, instead of simply replaying the original request.
        //
        if ($permissionName === Permission\Name::CREATE_PAYOUT)
        {
            $replayOriginalRequest = false;
        }

        if ($replayOriginalRequest === true)
        {
            // Not using App::call here because in Laravel6 this internally
            // matches function param names as well
            // calling resolveMethodDependencies so that all method dependencies
            // can be resolved.
            // Eg: postCreateCreditsLog(Credits\Service $service, $id)
            // In above case the 1st param will be resolved automatically

            $routeParams = Route::current()->resolveMethodDependencies(
                array_values($routeParams), new ReflectionMethod($controller, $functionName)
            );

            $internalResponse = $controller->$functionName(...array_values($routeParams));

            //
            // consider all non 2xx as failures
            //
            if (($internalResponse->getStatusCode() < 200) and
                ($internalResponse->getStatusCode() >= 300))
            {
                $state = State\Name::FAILED;
            }
        }

        // We're always going to set connection as Live because workflow are executed in live mode only
        // For test cases, please use live mode only, otherwise this break
        // TODO: This is a temporary solution, need to change this in future
        \Database\DefaultConnection::set(Mode::LIVE);

        // Update states

        $this->updateStateAndStateChanger($action, $state, $checkerEntity, $role);

        $stateCore->changeActionState($action, $state, $checkerEntity);

        $differCore->updateStateInEs($action->getId(), $state);

        return ['success' => true];
    }

    private function performMultipleWorkflowChanges($payload, $permissionName)
    {
        if (in_array($permissionName,ProductInternationalMapper::PRODUCT_PERMISSIONS_LIST))
        {
            $payload['permission'] = $permissionName;

            $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ["persmission added payload" => $payload]);
        }

        return $payload;
    }

    /**
     * @param string $entityId
     * @param string $entityType
     * @param string $action
     */
    public function handleOnboardingWorkflowActionIfOpen(string $entityId, string $entityType, string $action)
    {
        $actions = $this->fetchOpenActionOnEntityOperationWithPermissionList(
            $entityId, $entityType, Constants::ONBOARDING_WORKFLOWS);

        // If there are any action in progress
        if (empty($actions) === false)
        {
            $this->trace->info(TraceCode::HANDLE_WORKFLOW_ACTION_IF_OPEN,['entityId' => $entityId]);

            $maker = $this->app['workflow']->getWorkflowMaker();

            foreach ($actions as $workflowAction)
            {
                switch ($action)
                {
                    case State\Name::APPROVED:
                        $this->approveActionForcefully($workflowAction, $maker);
                        $this->updateStateAndStateChanger($workflowAction, State\Name::EXECUTED, $maker);
                        break;
                    case State\Name::REJECTED:
                        $this->applyActionRejectionStateChanges($workflowAction, $maker, null);
                        break;
                    case State\Name::CLOSED:
                        $this->close($workflowAction, $maker, true);
                        break;
                }
            }
        }
    }

    public function fetchLastUpdatedWorkflowActionInPermissionList(
        string $entityId,
        string $entityName,
        array $permissionNameList,
        string $orgId = null)
    {
        $permissionIdList = $this->fetchPermissionListId($permissionNameList, $orgId);

        $this->trace->info(
            TraceCode::GET_LAST_UPDATED_WORKFLOW_ACTION_IN_PERMISSION_IDS,
            ['permission_ids' => $permissionIdList]);

        $action = $this->repo
            ->workflow_action
            ->fetchLastUpdatedWorkflowActionInPermissionIds($entityId, $entityName, $permissionIdList);

        return $action;
    }

    public function getCurrentWorkflowTags($entityId, $entity, $permission, $orgId = null)
    {
        $action = $this->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [$permission],
            $orgId);

        if (empty($action) === true)
        {
            return [];
        }

        $tags = $action->getTagsAttribute();

        $tags =  $tags->map(function ($tag)
        {
            return $tag->slug;
        });

        return $tags;
    }

    public function addNeedClarificationComment(Entity $workFlowAction, array $input)
    {
        $comment = $this->getNeedWorkFlowClarificationComment($input);

        $commentEntity = (new CommentCore())->create([
            'comment' => $comment
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);

        return $commentEntity;
    }

    public function getNeedsClarificationBodyFromWorkflowComment($action)
    {
        if (empty($action) === true)
        {
            return null;
        }

        $comments = $this->repo->comment->fetchByActionId($action->getId());

        $comments =  $comments->map(function ($comment)
        {
            return $comment->toArrayPublic()['comment'];
        });

        foreach ($comments as $comment)
        {
            if (strpos($comment, Constants::NEEDS_WORKFLOW_CLARIFICATION_COMMENT_KEY) !== false)
            {
                return substr($comment, strlen(Constants::NEEDS_WORKFLOW_CLARIFICATION_COMMENT_KEY));
            }
        }

        return null;
    }

    public function trackSelfServeEventForNeedClarification(Entity $action)
    {
        $workflowPermission = $action->permission->getName();

        $diff = (new Differ\Service)->fetchRequest($action->getId());

        $payload = $diff[Differ\Entity::PAYLOAD];

        $merchantId = $this->getMerchantIdForWorkflowAction($action, $payload);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if (key_exists($workflowPermission, MerchantSelfServeObserver::PERMISSION_VS_SEGMENTS))
        {
            $segmentProperties = array_merge(
                [CONSTANTS::STATUS => CONSTANTS::NEEDS_CLARIFICATION]
            );

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, MerchantSelfServeObserver::PERMISSION_VS_SEGMENTS[$workflowPermission]);
        }
    }

    public function notifyMerchantForNeedClarification(Entity $action, array $input)
    {
        $workflowPermission = $action->permission->getName();

        $diff = (new Differ\Service)->fetchRequest($action->getId());

        $payload = $diff[Differ\Entity::PAYLOAD];

        $merchantId = $this->getMerchantIdForWorkflowAction($action, $payload);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $event = MerchantNotificationsConstants::WORKFLOW_PERMISSION_VS_NEEDS_CLARIFICATION_EVENT[$workflowPermission];

        if (in_array($workflowPermission, Constants::PERMISSION_FOR_NEW_SELF_SERVE_COMMUNICATIONS, true) === true)
        {
            $this->sendNewSelfServeNotification($workflowPermission, $merchant);

            return;
        }

        $params = array_merge($payload, [
            MerchantNotificationsConstants::MERCHANT_NAME                      => $merchant->getName(),
            MerchantNotificationsConstants::MESSAGE_SUBJECT                    => $input[Constants::MESSAGE_SUBJECT],
            MerchantNotificationsConstants::MESSAGE_BODY                       => $input[Constants::MESSAGE_BODY],
        ]);

        if (array_key_exists($event, MerchantNotificationsConstants::EVENT_VS_WORKFLOW_CLARIFICATION_SUBMIT_LINK) === true)
        {
            $params[MerchantNotificationsConstants::WORKFLOW_CLARIFICATION_SUBMIT_LINK] = MerchantNotificationsConstants::EVENT_VS_WORKFLOW_CLARIFICATION_SUBMIT_LINK[$event];
        }

        if(($workflowPermission === Permission\Name::INCREASE_TRANSACTION_LIMIT) and
           (isset($payload[MerchantEntity::MAX_PAYMENT_AMOUNT]) === true))
        {
            $params[MerchantEntity::MAX_PAYMENT_AMOUNT] = $payload[MerchantEntity::MAX_PAYMENT_AMOUNT]/100;
        }

        $args = [
            MerchantConstants::MERCHANT             => $merchant,
            DashboardEvents::EVENT                  => $event,
            MerchantNotificationsConstants::PARAMS  => $params
        ];

        if (array_key_exists($event, MerchantNotificationsConstants::CTA_TEMPLATES_VS_BUTTON_URL) === true)
        {
            $args[MerchantNotificationsConstants::IS_CTA_TEMPLATE]  = true;

            $args[MerchantNotificationsConstants::BUTTON_URL_PARAM] = MerchantNotificationsConstants::CTA_TEMPLATES_VS_BUTTON_URL[$event];
        }

        (new DashboardNotificationHandler($args))->send();
    }

    protected function getMerchantIdForWorkflowAction(Entity $action, $payload)
    {
        if (($action->getWorkflowEntityName() === Constants::MERCHANT) or
            ($action->getWorkflowEntityName() === Constants::MERCHANT_DETAIL))
        {
            return $action->getEntityId();
        }

        if (key_exists(Merchant\Entity::MERCHANT_ID, $payload) === true)
        {
            return $payload[Merchant\Entity::MERCHANT_ID];
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND_FOR_NEED_MERCHANT_CLARIFICATION);
    }

    protected function getNeedWorkFlowClarificationComment(array $input)
    {
        return Constants::NEEDS_WORKFLOW_CLARIFICATION_COMMENT_KEY . $input[Constants::MESSAGE_BODY];
    }

    public function getSelfServeActionForAnalyticsForNeedClarification(Entity $action)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $this->getSelfServeActionForNeedClarificationAnalytics($action, $segmentEventName, $segmentProperties);
    }

    protected function sendNewSelfServeNotification($permissionName, $merchant)
    {
        if (($permissionName === Permission\Name::EDIT_MERCHANT_BANK_DETAIL) and
            ($merchant->getOrgId() === OrgEntity::RAZORPAY_ORG_ID))
        {
            $this->sendNotificationForBankAccountUpdate($merchant);

            return;
        }
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function getSelfServeActionForNeedClarificationAnalytics(Entity $action, string $segmentEventName, array &$segmentProperties)
    {
        $workflowPermission = $action->permission->getName();

        $diff = (new Differ\Service)->fetchRequest($action->getId());

        $payload = $diff[Differ\Entity::PAYLOAD];

        $merchantId = $this->getMerchantIdForWorkflowAction($action, $payload);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if (key_exists($workflowPermission, MerchantSelfServeObserver::PERMISSION_FOR_NEED_CLARIFICATION_SEGMENT_ACTION_NAME))
        {
            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] =
                MerchantSelfServeObserver::PERMISSION_FOR_NEED_CLARIFICATION_SEGMENT_ACTION_NAME[$workflowPermission];

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    protected function sendNotificationForBankAccountUpdate($merchant, $event = DashboardEvents::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION)
    {
        $merchantBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        $bankAccountNumber = $merchantBankAccount->getAccountNumber();

        $last_3 = substr($bankAccountNumber, -3);

        $merchantConfig = [];

        if ($merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true)
        {
            $input[Merchant\Constants::MERCHANT_ID] = $merchant->getMerchantId();

            $merchantConfig = (new SettlementService)->merchantConfigGet($input);
        }

        $isMerchantSettlementsOnHold = (new BankAccount\Core)->isMerchantSettlementsOnHold($merchantConfig);

        if ($isMerchantSettlementsOnHold === true)
        {
            $event = DashboardEvents::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION;
        }

        $args = [
            Merchant\Constants::MERCHANT     => $merchant,
            DashboardEvents::EVENT           => $event,
            Merchant\Constants::PARAMS       => [
                MerchantNotificationsConstants::MERCHANT_NAME => $merchant[Merchant\Entity::NAME],
                MerchantNotificationsConstants::LAST_3        => '**' . $last_3,
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }
}
