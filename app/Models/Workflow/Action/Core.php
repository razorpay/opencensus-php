<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Workflow\Action\Timeline;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
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
            if ($checker->getStatus() !== State::APPROVED)
            {
                $actionApproved = false;

                break;
            }
        }

        if ($actionApproved === true)
        {
            $this->approveAction($action);
        }
    }

    protected function approveAction(Entity $action)
    {
        $data = [
            Entity::APPROVED => true,
        ];

        $action->edit($data);

        $this->repo->saveOrFail($action);

        return $action;
    }
}
