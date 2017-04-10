<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Base;
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

        $routePermissions = $input[Differ\Entity::PERMISSIONS];

        // Not all route permissions could be present in admin.
        $commonPermissions = array_intersect($routePermissions, $adminPermissions);

        $permissionIds = $this->repo
                              ->permission
                              ->retrieveIdsByNamesAndOrg($commonPermissions, $admin->getOrgId())
                              ->map(function ($permission){
                                    return $permission->getId();
                                })
                              ->toArray();

        $workflows = $this->repo->workflow->fetchWorkflowsByPermissions($permissionIds);

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

    public function checkAndMarkActionApproved(Entity $action)
    {
        if ($action->getApproved() === true)
        {
            return true;
        }

        $actionId = $action->getId();

        $workflowId = $action->getWorkflowId();

        // 1. Get total checker approvals

        $checkerApprovalCount = $this->repo
                                     ->action_checker
                                     ->fetchApprovedCountByActionId($actionId);

        // 2. Get total checker approvals required

        $requiredCheckersCount = $this->repo
                                      ->workflow_step
                                      ->getNumCheckers($workflowId);

        // If current checker approvals count doesn't match
        // the required checker approvals then don't do anything
        if ($checkerApprovalCount !== $requiredCheckersCount)
        {
            return false;
        }

        $action = $this->approveAction($action);

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

    public function updateCurrentLevelIfNeeded(Entity $action)
    {
        // 1. Get the total reviewer_count required across all
        // the roles (all the workflow_step entries)
        // for the current level of the action

        $level = $action->getCurrentLevel();

        $workflowId = $action->getWorkflowId();

        $steps = $this->repo
                      ->workflow_step
                      ->findByLevelAndWorkflowId($level, $workflowId);

        $totalReviewerCount = 0;

        $stepIds = [];

        foreach ($steps as $step)
        {
            $totalReviewerCount += $step->getReviewerCount();

            $stepIds[] = $step->getId();
        }

        // 2. Get total number of people who have approved (checked) this action

        $totalCheckerApprovals = $this->repo
                                      ->action_checker
                                      ->fetchApprovedCountByActionIdAndStepIds(
                                          $action->getId(), $stepIds);

        // 3. Check if there is any level (or step basically)
        // after workflow_actions.current_level

        $nextLevelStep = $this->repo
                          ->workflow_step
                          ->getNextLevelOfWorkflowId($level, $workflowId);

        // 4. Finally if there's a next level AND
        // total approvals received is more than
        // total reviewer count (approvals) required then
        // update the level of the action.

        if ((empty($nextLevelStep) === false) and
            ($totalCheckerApprovals >= $totalReviewerCount))
        {
            $this->repo->transactionOnLiveAndTest(function () use ($action, $nextLevelStep) {

                $action->setCurrentLevel( $nextLevelStep->getLevel());

                $this->repo->saveOrFail($action);
            });
        }
    }

    public function fetchOpenWorkflows(string $workflowId)
    {
        return $this->repo->workflow_action->findOpenWorkflows($workflowId);
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
