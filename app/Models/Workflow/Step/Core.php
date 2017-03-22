<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;

class Core extends Base\Core
{
    public function create(Workflow\Entity $workflow, array $input)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($step)
        {
            $this->repo->saveOrFail($step);
        });

        return $step;
    }
}
