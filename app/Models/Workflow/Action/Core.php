<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action\State;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Workflow\Action\Checker;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $action = new Entity;

        $action->generateId();

        $admin = $this->app['basicauth']->getAdmin();

        $params = [
            Entity::ORG_ID      => $admin->getOrgId(),
            Entity::ADMIN_ID    => $admin->getId()
        ];

        $adminPermissions = $admin->getPermissionsList();

        $orgId = $admin->getOrgId();

        $routePermissions = $input[Differ\Entity::PERMISSIONS];

        // Not all route permissions could be present in admin.
        $commonPermissions = array_intersect($routePermissions, $adminPermissions);

        $workflows = $this->getWorkflowsForPermissions($commonPermissions, $orgId);

        // More than one workflow could be found.
        $workflow = $workflows->first();

        $params[Entity::WORKFLOW_ID] = $workflow->getId();

        $params[Entity::DIFFER] = $input;

        $action->build($params);

        $this->repo->transactionOnLiveAndTest(function() use($action, $params)
        {
            $this->repo->saveOrFail($action);

            $this->createInitialStateForAction($action);

            $differ = $params[Entity::DIFFER];

            unset($differ[Entity::ORG_ID]);

            unset($differ[Differ\Entity::PERMISSIONS]);

            (new Differ\Core)->create($action, $differ);
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
    public function getWorkflowsForPermissions(array $permissions, string $orgId)
    {
        // Implicit check for permission existance in the organisation.
        $permissionIds = $this->repo
                              ->permission
                              ->retrieveIdsByNamesAndOrg($permissions, $orgId)
                              ->map(function ($permission){
                                    return $permission->getId();
                                })
                              ->toArray();

        // Implicit check for workflow in the organisation against permission ids.
        $workflows = $this->repo
                          ->workflow
                          ->fetchWorkflowsByPermissionsAndOrgId($permissionIds, $orgId);

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

    public function edit(Entity $action, array $input)
    {
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
}
