<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Workflow\Step;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = new Entity;

        $workflow->generateId();

        $workflow->build($input);

        // Create the steps and workflow in a single transaction
        $this->repo->transactionOnLiveAndTest(function() use($workflow)
        {
            $this->repo->saveOrFail($workflow);

            foreach ($step as $input['steps'])
            {
                $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                (new Step\Core)->create($step);
            }

            $workflow->permissions()->sync($input['permissions']);
        });

        return $workflow;
    }
}
