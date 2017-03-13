<?php

namespace RZP\Models\Workflow\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Workflow\Action\Timeline;
use RZP\Models\Workflow\Action\State;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $timeline = new Entity;

        $timeline->generateId();

        $timeline->build($input);

        $this->repo->saveOrFail($input);
    }
}
