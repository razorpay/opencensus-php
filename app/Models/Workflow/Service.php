<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $workflow = $this->core()->create($input);

        return $workflow->toArrayPublic();
    }

    public function fetch(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        return $workflow->toArrayPublic();
    }
}
