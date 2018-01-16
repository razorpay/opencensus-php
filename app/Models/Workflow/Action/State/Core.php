<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Models\Workflow\Base;
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

    public function changeActionState(
        string $actionId,
        string $state,
        string $adminId = null)
    {
        $input = [
            State\Entity::ACTION_ID  => $actionId,
            State\Entity::ADMIN_ID   => $adminId,
            State\Entity::NAME       => $state,
        ];

        $this->create($input);
    }
}
