<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Step;
use RZP\Models\Admin\Role;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = (new Entity)->generateId();

        // $workflow->generateId();

        $workflow->build($input);



        // Create the steps and workflow in a single transaction
        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            foreach ($input['steps'] as $step)
            {
                $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                Role\Entity::verifyIdAndStripSign($step['role_id']);

                (new Step\Core)->create($step);
            }


            $workflow->permissions()->sync($input['permissions']);
        });

        return $workflow;
    }

    public function update(Entity $workflow, array $input)
    {
        $workflow->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use($workflow)
        {
            $this->repo->saveOrFail($workflow);

            $workflow->permissions()->sync($input['permissions']);
        });

        return $workflow;
    }

    public function delete(Entity $workflow)
    {
        $openWorkflows = (new Workflow\Action)->fetchOpenWorkflows($workflow->getId());

        if (empty($openWorkflows) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED);
        }

        return $this->repo->workflow->deleteOrFail($workflow);
    }


}
