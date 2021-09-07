<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;

class Service extends Base\Service
{
    public function get(string $actionId)
    {
        Action\Entity::verifyIdAndSilentlyStripSign($actionId);

        $diff = $this->core()->get($actionId);

        return $diff;
    }

    public function fetchRequest(string $actionId)
    {
        Action\Entity::verifyIdAndSilentlyStripSign($actionId);

        return $this->core()->fetchRequest($actionId);
    }
}
