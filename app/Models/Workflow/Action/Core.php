<?php

namespace RZP\Models\Workflow\Action;

use App;
use Request;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action\State;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Checker;
use RZP\Constants\Mode;

use RZP\Models\Admin\Org;
use RZP\Models\Workflow;
use RZP\Models\Admin\Permission;

class Core extends Base\Core
{
    private function buildParams(array $input) : array
    {
        $admin = $this->app['basicauth']->getAdmin();

        $params = [
            Entity::ORG_ID      => $admin->getOrgId(),
            Entity::ADMIN_ID    => $admin->getId()
        ];

        $adminPermissions = $admin->getPermissionsList();

        $orgId = $admin->getOrgId();

        $routePermission = $input[Differ\Entity::PERMISSION];

        // Implicit check for permission existance in the organisation.
        $permissionId = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg($routePermission, $orgId)
                             ->toArray()[0];

        // We don't need to check the following 2 things:
        //
        // - Whether a workflow exists against the routePermission
        // because this is already done in workflow middleware
        //
        // - Whether the admin has access to this permission because
        // that is also done in the middleware or should be done
        // from whereever this code is called/triggered.

        // Currently single permission can have only 1 workflow
        // App level checks are in place. But this is sort of progressive
        // code where a single permission might have multiple workflows
        // in future.
        $workflows = $this->getWorkflowsForPermission($permissionId, $orgId);

        // More than one workflow could be found.
        $workflow = $workflows->first();

        $params[Entity::WORKFLOW_ID] = $workflow->getId();

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

        return $params;
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

        $params = [
            Entity::ORG_ID          => Org\Entity::$strip($input[Entity::ORG_ID]),
            Entity::ADMIN_ID        => Admin\Entity::$strip($input[Entity::ADMIN_ID]),
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
    public function create(array $input, $retry = false)
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
            $params = $this->buildParams($input);
        }

        $this->repo->transactionOnLiveAndTest(function() use ($action, $params, $retry)
        {
            $differInput = $params[Entity::DIFFER] ?? null;

            unset($params[Entity::DIFFER]);

            $action->build($params);

            $this->repo->saveOrFail($action);

            $this->createInitialStateForAction($action);

            if (($retry === false) and (empty($differInput) === false))
            {
                unset($differInput[Entity::ORG_ID]);

                // Create the diff for the entity
                (new Differ\Core)->create($action, $differInput);
            }
        });

        return $action;
    }

    protected function createInitialStateForAction(Entity $action)
    {
        $input = [
            State\Entity::ACTION_ID  => $action->getId(),
            State\Entity::ADMIN_ID   => $action->getAdminId(),
            State\Entity::NAME       => State\Entity::OPEN,
        ];

        $actionState = (new State\Core)->create($input);

        return $actionState;
    }

    /**
     * Fetch workflows mapped to the permissions for this organisation.
     * This checks for if the permission is present for the organisation
     * and if a workflow is mapped gainst the permission.
     *
     * @param array $permissions
     * @param string $orgId
     * @return array
     **/
    public function getWorkflowsForPermission(string $permissionId, string $orgId)
    {
        // Implicit check for workflow in the organisation against permission ids.
        $workflows = $this->repo
                          ->workflow
                          ->fetchWorkflowsByPermissionsAndOrgId($permissionId, $orgId);

        return $workflows;
    }

    /**
     * This function has to run in a transaction
     */
    public function checkAndMarkActionApproved(Entity $action)
    {
        if ($action->getApproved() === true)
        {
            return true;
        }

        $actionId = $action->getId();

        $workflowId = $action->getWorkflowId();

        $lastLevel = $this->repo->workflow_step
                                ->getLastLevelOfWorkflow($workflowId);

        if ($lastLevel !== $action->getCurrentLevel())
        {
            return false;
        }

        if ($this->isCurrentLevelApproved($action) === true)
        {
            $this->approveAction($action);
        }

        return true;
    }

    protected function approveAction(Entity $action)
    {
        // Set the action as approved and create a state change that it has
        // been moved to approved.
        $this->repo->transactionOnLiveAndTest(function() use ($action)
        {
            $data = [
                Entity::APPROVED => true,
                Entity::STATE    => State\Entity::APPROVED,
            ];

            $action->edit($data);

            $this->repo->saveOrFail($action);

            $stateData = [
                State\Entity::ACTION_ID => $action->getId(),
                State\Entity::ADMIN_ID  => $action->getAdminId(),
                State\Entity::NAME      => State\Entity::APPROVED,
            ];

            (new State\Core)->create($stateData);

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

        $opType = $steps[0]->getOpType();

        $stepIds = [];

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
        $stepCheckerMap = [];

        foreach ($totalCheckerApprovals as $approval)
        {
            $stepId = $approval[Checker\Entity::STEP_ID];

            $stepCheckerMap[$stepId] = $approval['total'];
        }

        $stepApprovedMap = [];

        foreach ($stepReviewCountMap as $stepId => $reviewCount)
        {
            $approvalCount = $stepCheckerMap[$stepId] ?? 0;

            $stepApprovedMap[$stepId] = ($approvalCount === $reviewCount);
        }

        // If the reviewers in a single step approved
        // Based on the op type, we do an AND or OR operation on approvals per
        // step basis.
        // If step1 or step2. one of the steps's approvals should match
        // reviewer count without a single rejection by either side.
        //

        $levelApproved = false;

        if ($opType === Step\Entity::OP_TYPE_AND)
        {
            // if any of the check fails, level is not approved.
            $levelApproved = (in_array(false, $stepApprovedMap, true) === false);
        }
        else if ($opType === Step\Entity::OP_TYPE_OR)
        {
            // if any of the check passed, level is approved.
            $levelApproved = in_array(true, $stepApprovedMap, true);
        }

        return $levelApproved;
    }

    /**
     * This function has to run in a transaction
     */
    public function updateCurrentLevelIfNeeded(Entity $action)
    {
        // Get the total reviewer_count required across all
        // the roles (all the workflow_step entries)
        // for the current level of the action

        // Only open actions are supported
        if ($action->getState() !== State\Entity::OPEN)
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
        $action->getValidator()->validateActionIsOpen($action);

        $action->edit($input);

        $this->repo->saveOrFail($action);

        return $action;
    }

    public function close(Entity $action, Admin\Entity $admin)
    {
        $action->getValidator()->validateCloseAction($admin);

        $this->repo->transactionOnLiveAndTest(function () use($action, $admin){

            $state = State\Entity::CLOSED;

            $stateData = [
                State\Entity::ACTION_ID => $action->getId(),
                State\Entity::ADMIN_ID  => $admin->getId(),
                State\Entity::NAME      => $state,
            ];

            $this->updateState($action, $state);

            (new State\Core)->create($stateData);

            (new Differ\Core)->updateStateInEs(
                $action->getId(), $stateData[State\Entity::NAME]);
        });
    }

    public function updateState(Entity $action, string $state)
    {
        $input = [
            Entity::STATE => $state,
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
        string $permissionName)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        $permissionId = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg($permissionName, $orgId)
                             ->toArray()[0];

        $actions = $this->repo
                               ->workflow_action
                               ->getOpenActionOnEntityOperation(
                                   $entityId, $entityName, $permissionId);

        return $actions;
    }

    public function executeAction($action)
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

        // Replace the current request's payload with the
        // actual maker request payload.
        Request::replace($payload);

        // Create controller object
        $controller = App::make($controller);

        // Auth details have to be initialized before
        // the actual code (Controller@action) runs.
        $this->initAuthDetails($authDetails);
        s($this->app['db']->connection()->getDatabaseName());
        $internalResponse = App::call([$controller, $functionName], array_values($routeParams));

        $state = State\Entity::EXECUTED;

        if ($internalResponse->getStatusCode() !== 200)
        {
            $state = State\Entity::FAILED;
        }

        // The connection is being reset in here because after executing the App::call
        // If the connection is still set to test then this will fail cause workflow exists only in live mode.
        if ($this->app->environment('testing') === false)
        {
            $mode = Mode::LIVE;
        }
        else
        {
            $mode = Mode::TEST;
        }

        // Resetting connection so that workflow updates will not fail for test modes.
        \Database\DefaultConnection::set($mode);

        $adminId = $this->app['basicauth']->getAdmin()->getId();

        // Update states

        $this->updateState($action, $state);

        $stateCore->changeActionState($action->getId(), $state, $adminId);

        $differCore->updateStateInEs($action->getId(), $state);

        return ['success' => true];
    }
}
