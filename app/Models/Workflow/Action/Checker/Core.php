<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $action = $this->repo->workflow_action->findOrFailPublic(
            $input[Entity::ACTION_ID]);

        $currentLevel = $action->getCurrentLevel();

        $workflowId = $action->workflow->getId();

        $roleId = $admin->role->getId();

        // Assumption is only one step should be returned here.
        $step = $this->repo->workflow_steps
                           ->findByLevelWorkflowIdAndRoleId($currentLevel, $workflowId, $roleId)
                           ->first();

        // Maker is the authorized admin from whom we got the request
        $input[Entity::ADMIN_ID] = $admin->getId();

        $input[Entity::STEP_ID] = $step->getId();

        // Create action_checker Entity
        $checker = new Entity;

        $checker->generateId();

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($checker)
        {
            $this->repo->saveOrFail($checker);

            // If the checker is final approver or rejects an action,
            // Create a state transition for the action
            $this->createStateTransitionForChecker($checker);

        });

        (new Action\Core)->updateCurrentLevelIfNeeded($action, $step, $admin);

        // TODO can do it async using laravel events
        (new Action\Core)->checkAndMarkActionApproved();

        return $checker;
    }

    // TO CHECK
    protected function createStateTransitionForChecker(Entity $checker)
    {
        $state = $checker->getStatusOnAction();

        if ($state === null)
        {
            return;
        }

        if ($state === State\Entity::REJECTED)
        {
            $this->createStateTransitionOnRejection();
        }

        (new Action\Core)->checkIfActionApproved();
    }

    protected function createStateTransitionOnRejection()
    {
        $input = [
            State\Entity::NAME      => State\Entity::REJECTED,
            State\Entity::ACTION_ID => $checker->getActionId(),
            State\Entity::ADMIN_ID  => $checker->getAdminId(),
        ];

        (new State\Core)->create($input);
    }
}
