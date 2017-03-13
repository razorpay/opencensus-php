<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Timeline;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $checker = new Entity;

        $checker->generateId();

        $validator = $checker->getValidator();

        $validator->validateCheckerIsNotMaker(
            $admin->getId(),
            $input[Entity::ADMIN_ID]);

        $checker->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($checker)
        {
            $this->repo->saveOrFail($checker);

            $this->createTimelineEventForChecker($checker);
        });

        (new Action\Core)->checkIfActionApproved();
    }

    public function createTimelineEventForChecker(Checker\Entity $checker)
    {
        $state = $checker->getStatusOnAction();

        // Checker has not yet approved or rejected the request
        if ($state === State::UNDER_REVIEW)
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
