<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;

class Service extends Base\Service
{
    public function get(string $wid, string $stepId, array $input)
    {
        Entity::verifyIdAndStripSign($stepId);

        Workflow\Entity::verifyIdAndStripSign($wid);

        $step = $this->repo->fetchByWorkflowIdAndStepId($wid, $stepId);

        return $step->toArrayPublic();
    }
}
