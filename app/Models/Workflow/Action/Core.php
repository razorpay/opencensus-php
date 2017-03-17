<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $action = new Entity;

        $action->generateId();

        $action->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($action) {

            $this->repo->saveOrFail($action);

            $this->createInitialStateForAction($action);
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
        $checkers = $this->repo->action_checker->fetchByActionId(
            $action->getId());

        $workflowId = $action->getWorkflowId();

        // Number of checks required for the action
        $numCheckers = $this->repo
                            ->workflow_step
                            ->getNumCheckersForWorkflow($workflowId);

        // If all checks are not done, do not review the action
        if (count($checkers) !== $numCheckers)
        {
            return;
        }

        $actionApproved = false;

        // If all the checkers have reviewed and approved
        // approve the action for execution
        foreach ($checkers as $checker)
        {
            if ($checker->getStatus() !== State\Entity::APPROVED)
            {
                $actionApproved = false;

                return false;
            }
        }

        if ($actionApproved === true)
        {
            $action = $this->approveAction($action);
        }

        return true;
    }

    protected function approveAction(Entity $action)
    {
        // Set the action as approved and create a state change
        $this->repo->transactionOnLiveAndTest(function() use($action)
        {
            $data = [
                Entity::APPROVED => true,
            ];

            $action->edit($data);

            $this->repo->saveOrFail($action);

            $stateData = [
                State\Entity::ACTION_ID => $action->getId(),
                State\Entity::NAME      => State\Entity::APPROVED,
            ];

            (new State\Core)->create($stateData);
        });

        return $action;
    }
}
