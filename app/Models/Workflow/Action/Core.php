<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
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

        $this->repo->transactionOnLiveAndTest(function() use($action, $params) {

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
        // Number of checks done on the action
        // TODO fetch the checker count instead of all the checkers
        // save query time
        $checkers = $this->repo->action_checker->fetchByActionId($action->getId());

        $workflowId = $action->getWorkflowId();

        // Number of checks required for the action
        $numCheckers = $this->repo
                            ->workflow_step
                            ->getNumCheckers($workflowId);

        // If all checks are not done, do not review the action
        if (count($checkers) !== $numCheckers)
        {
            return;
        }

        $actionApprovedByCheckers = false;

        // If all the checkers have reviewed and approved
        // approve the action for execution
        foreach ($checkers as $checker)
        {
            if ($checker->getStatus() !== State\Entity::APPROVED)
            {
                $actionApprovedByCheckers = false;

                return false;
            }
        }

        if ($actionApprovedByCheckers === true)
        {
            $action = $this->approveAction($action);
        }

        return true;
    }

    protected function approveAction(Entity $action)
    {
        // Set the action as approved and create a state change that it has
        // been moved to approved.
        $this->repo->transactionOnLiveAndTest(function() use($action)
        {
            $data = [
                Entity::APPROVED => true,
            ];

            $action->edit($data);

            $this->repo->saveOrFail($action);

            $stateData = [
                State\Entity::ACTION_ID => $action->getId(),
                State\Entity::ADMIN_ID  => $action->getAdminId(),
                State\Entity::NAME      => State\Entity::APPROVED,
            ];

            (new State\Core)->create($stateData);
        });

        return $action;
    }

    public function updateCurrentLevelIfNeeded(Entity $action, $step, $admin)
    {
        // 1. Get the total reviewer_count required across all
        // the roles (all the workflow_step entries)
        // for the current level of the action

        $level = $action->getCurrentLevel();

        $workflow = $action->workflow;

        $steps = $this->repo
                      ->workflow_step
                      ->findByLevelAndWorkflowId($level, $workflow->getId());

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
                                      ->fetchCountByActionIdAndStepIds($action->getId(), $stepIds);

        // 3. Finally if total approvals received is more than
        // total reviewer count (approvals) required then
        // update the level of the action

        if ($totalCheckerApprovals >= $totalReviewerCount)
        {
            $this->repo->transactionOnLiveAndTest(function () use ($action) {
                $action->current_level += 1;

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

    public function edit(string $actionId, array $input)
    {
         $action = $this->repo->workflow_action->findOrFailPublic($actionId);

         $this->repo->transactionOnLiveAndTest(function() use($action, $input)
         {
             $action->edit($input);

             $this->repo->workflow_action->saveOrFail($action);
         });

         return $action;
    }
}
