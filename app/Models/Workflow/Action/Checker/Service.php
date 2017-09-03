<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Service extends Base\Service
{
    public function create(string $actionId, array $input)
    {
        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $input[Entity::ACTION_ID] = $actionId;

        $checker = $this->core()->create($input);

        return $checker->toArrayPublic();
    }
}
