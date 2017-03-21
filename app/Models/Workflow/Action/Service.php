<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Entity as Action;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $action = $this->core()->create($input);

        return $action->toArrayPublic();
    }
}
