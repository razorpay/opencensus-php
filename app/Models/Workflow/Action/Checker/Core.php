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

        $checker = new Entity;

        $checker->generateId();

        $validator = $checker->getValidator();

        $action = $this->repo->workflow_action->findByPublicId(
            $input[Entity::ACTION_ID]);

        $validator->validateCheckerIsNotMaker(
            $admin->getId(),
            $action->getAdminId());

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($checker)
        {
            $this->repo->saveOrFail($checker);

            $this->createStateTransitionForChecker($checker);
        });

        (new Action\Core)->checkIfActionApproved();
    }

    public function createStateTransitionForChecker(Checker\Entity $checker)
    {
        $state = $checker->getStatusOnAction();

        if ($state === null)
        {
            return;
        }

        $input = [
            Entity::STATE     => $state,
            Entity::ACTION_ID => $checker->getActionId(),
            Entity::ADMIN_ID  => $checker->getAdminId(),
        ];

        $this->create($input);
    }
}
