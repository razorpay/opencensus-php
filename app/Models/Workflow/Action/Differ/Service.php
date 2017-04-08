<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        $diff = $this->core()->create($actionId, $input);

        return ['action_id' => $diff->getId()];
    }

    public function get(string $actionId)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        $diff = $this->core()->get($actionId);

        return $diff;
    }

    public function fetchRequest(string $actionId)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        $action = $this->repo->workflow_action->findOrFailPublic($actionId);

        return $this->core()->fetchRequest($action);
    }

    public function changeActionState(
        string $actionId,
        State\Entity $state,
        string $adminId = null)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        (new State\Core)->changeActionState($actionId, $state, $adminId);
    }
}
