<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        $diff = $this->core()->create($actionId, $input);

        return ['action_id' => $diff->getId()];
    }

    public function get(string $actionId)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        $diff = $this->core()->get($id);

        return $diff;
    }

    public function fetchRequest(string $actionId)
    {
        Action\Entity::verifyIdAndStripSign($actionId);

        return $this->core()->fetchRequest($actionId);
    }
}
