<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Step;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = new Entity;

        // Gets org id from input for now.
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

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

            $permissionIds = Permission\Entity::verifyIdAndStripSignMultiple(
                $input['permissions']);

            $workflow->permissions()->sync($permissionIds);
        });

        return $workflow;
    }
}
