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

        $routePermissions = $input[Differ\Entity::PERMISSIONS];

        $workflow = $this->getMinLeveledWorkflow($routePermissions, $admin->getOrgId());

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

    public function updateCurrentLevelIfNeeded(Entity $action)
    {
        //
        // get all the checkers in the current level
        // fetch the reviewer count in the current level of steps
        // if checkers === reviewer count, update the current level
        //

        $level = $action->getCurrentLevel();

        $requiredCheckers = $this->repo->workflow_step->getNumCheckersByLevelAndWorkflowId(
            $level, $action->getWorkflowId());

        $numCheckers = $this->repo->action_checker->fetchCountByActionId($action->getId());

        // If all the checkers in the same level have given their review,
        // increment the level
        if ($numCheckers === $requiredCheckers)
        {
            $action->incrementCurrentLevel();

            $this->repo->saveOrFail($action);
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
}
