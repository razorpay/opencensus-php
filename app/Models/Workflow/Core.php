<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Admin\Org;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = new Entity;

        // Gets org id from input for now.
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        $workflow->generateId();

        $workflow->build($input);

        $this->repo->transactionOnLiveAndTest(function() use($workflow)
        {
            $this->repo->saveOrFail($workflow);
        });

        return $workflow;
    }
}
