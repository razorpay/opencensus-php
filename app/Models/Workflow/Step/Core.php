<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;
use RZP\Models\Workflow;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $this->repo->saveOrFail($step);

        return $step;
    }

    public function update(Entity $step, array $input)
    {
        $step->edit($input);

        $this->repo->saveOrFail($step);

        return $step;
    }

    public function delete(string $wid, string $stepId)
    {

    }
}
