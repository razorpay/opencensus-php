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

        // In future if an admin can have multiple roles
        // we could get more than 1 step in this call.
        $steps = $this->repo->workflow_step
                            ->findByLevelWorkflowIdAndRoleId($currentLevel, $workflowId, $roleIds);

        $checkNotRequired = false;

        // In the current level (given that exists and is
        // supposed to be worked upon), no checking is required
        // from the checker's (assigned) roles.
        if ($steps->count() === 0)
        {
            $checkNotRequired = true;
        }

        foreach ($steps as $step)
        {
            // Check if $step requires any check by matching
            // workflow_step.reviewer_count with count(action_checkers)

            $requiredReviewerCount = $step->getReviewerCount();

            $totalActionCheckers = $this->repo
                                        ->action_checker
                                        ->fetchCountByActionIdForStep(
                                            $action->getId(), $step->getId());

            // For a particular step (in current foreach context)
            // check may not be required hence we set $checkNotRequired
            // to `true`. But in the next step if it is required
            // then we'll set $checkNotRequired to false and break
            // from the loop. We'll continue working with the step for which
            // check IS required.
            if ($totalActionCheckers >= $requiredReviewerCount)
            {
                $checkNotRequired = true;
            }
            else
            {
                $checkNotRequired = false;

                // Once we break from foreach $step will be the one
                // in current context right before break. $step in current
                // context will be used for further operations.
                break;
            }
        }

        if ($checkNotRequired === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL);
        }

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

        // Once all the roles x reviewer_count have approved
        // an action, we need to update the level so that
        // we can show the action to next level/step checkers
        (new Action\Core)->updateCurrentLevelIfNeeded($action, $step, $admin);

        // If all the checkers have approved then approve
        // and close the action
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
