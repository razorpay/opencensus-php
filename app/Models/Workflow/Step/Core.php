<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Base;

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

}
