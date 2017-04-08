<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        // When a checker request is made, we need to first
        // verify whether the admin user can check the action
        // as well as whether we need any more approvals
        // for the action at the current level or not.

        // Get checker roles

        // todo: there can be multiple roles
        // todo: we are not doing check against action_checker

        $admin = $this->app['basicauth']->getAdmin();

        $roleIds = $admin->roles()->getRelatedIds()->toArray();

        // We will need workflow ID and current level
        // of the action in context. So get the action first and then
        // fetch the others.

        $action = $this->repo->workflow_action->findOrFailPublic(
            $input[Entity::ACTION_ID]);

        $currentLevel = $action->getCurrentLevel();

        $workflowId = $action->workflow->getId();

        // Assumption is only one step should be returned here.
        //
        // THIS is the action's current step's definition
        $step = $this->repo->workflow_step
                           ->findByLevelWorkflowIdAndRoleId($currentLevel, $workflowId, $roleIds)
                           ->first();

        // In the current level (given that exists and is
        // supposed to be worked upon), no checking is required
        // from the checker's (assigned) roles.
        if (empty($step))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL);
        }
        sd($step);
        // ADMIN_ID is the checker's ID (current request's admin)
        $input[Entity::ADMIN_ID] = $admin->getId();

        // Set the step for which checker is checking
        $input[Entity::STEP_ID] = $step->getId();

        // Create and save the action_checker Entity
        $checker = new Entity;

        $checker->generateId();

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use ($checker)
        {
            $this->repo->saveOrFail($checker);

            // If the checker is final approver or rejects an action,
            // Create a state transition for the action
            $this->createStateTransitionForChecker($checker);
        });

        // Why is this important ?
        (new Action\Core)->updateCurrentLevelIfNeeded($action, $step, $admin);
sd('checker created and current level updated');
        // TODO can do it async using laravel events
        (new Action\Core)->checkAndMarkActionApproved();

        return $checker;
    }

    // TO CHECK
    protected function createStateTransitionForChecker(Entity $checker)
    {
        return; // since the methods used here don't exist, we'll refactor

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
