<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Exception;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $actionState = new Entity;

        $actionState->generateId();

        $actionState->build($input);

        $this->repo->saveOrFail($actionState);

        return $actionState;
    }

    public function changeActionState(Action\Entity $action, string $state)
    {
        $input = [
            State\Entity::ACTION_ID  => $action->getId(),
            State\Entity::ADMIN_ID   => $action->getAdminId(),
            State\Entity::NAME       => $state,
        ];

        $this->create($input);
    }
}
