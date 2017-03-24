<?php

namespace RZP\Models\Workflow\Action;

use RZP\Models\Base;
use RZP\Models\Workflow\Action\Differ;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $action = $this->core()->create($input);

        $diff = (new Differ\Core)->fetchRequest($action);

        $diff = $diff[Differ\Entity::DIFF];

        $result = $action->toArrayPublic();

        $result['diff'] = $diff;

        return $result;
    }

    public function get(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $action = $this->core()->get($id);

        return $action->toArrayPublic();
    }
}
