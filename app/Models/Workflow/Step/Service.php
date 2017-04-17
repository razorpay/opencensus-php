<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Admin\Role;

class Service extends Base\Service
{
    public function fetch(string $wid, string $stepId)
    {
        Entity::verifyIdAndStripSign($stepId);

        Workflow\Entity::verifyIdAndStripSign($wid);

        $step = $this->repo->workflow_step->fetchByWorkflowIdAndStepId($wid, $stepId);

        return $step->toArrayPublic();
    }

    public function fetchMultiple(string $wid)
    {
        Workflow\Entity::verifyIdAndStripSign($wid);

        $step = $this->repo->workflow_step->fetchByWorkflowId($wid);

        return $step->toArrayPublic();
    }

    public function create(string $wid, array $input)
    {
        Workflow\Entity::verifyIdAndStripSign($wid);

        Role\Entity::verifyIdAndStripSign($input[Entity::ROLE_ID]);

        $input[Entity::WORKFLOW_ID] = $wid;

        $step = $this->core()->create($input);

        return $step->toArrayPublic();
    }
}
