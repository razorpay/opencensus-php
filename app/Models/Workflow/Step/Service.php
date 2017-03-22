<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;

class Service extends Base\Service
{
    public function create(string $id, array $input) : array
    {
        Workflow::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $step = $this->core()->create($workflow, $input);

        return $step->toArrayPublic();
    }
}
