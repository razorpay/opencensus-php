<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Admin\Role;

class Service extends Base\Service
{
    public function fetchMultiple(string $wid)
    {
        Workflow\Entity::verifyIdAndStripSign($wid);

        $step = $this->repo->workflow_step->fetchByWorkflowId($wid);

        return $step->toArrayPublic();
    }
}
