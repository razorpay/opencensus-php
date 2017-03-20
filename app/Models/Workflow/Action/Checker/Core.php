<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $input[Entity::ADMIN_ID] = $admin->getId();

        $checker = new Entity;

        $checker->generateId();

        $validator = $checker->getValidator();

        $action = $this->repo->workflow_action->findByPublicId(
            $input[Entity::ACTION_ID]);

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($checker)
        {
            $this->repo->saveOrFail($checker);

            $this->createStateTransitionForChecker($checker);

        });

        // TODO can do it async using laravel events
        (new Action\Core)->checkAndMarkActionApproved();

        return $checker;
    }

    protected function createStateTransitionForChecker(Checker\Entity $checker)
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
