<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Step;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = (new Entity)->generateId();

        $workflow->build($input);

        // Create the steps and workflow in a single transaction
        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            foreach ($input[Entity::STEPS] as $step)
            {
                $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                Role\Entity::verifyIdAndStripSign($step[Step\Entity::ROLE_ID]);

                (new Step\Core)->create($step);
            }

            $workflow->permissions()->sync($input[Entity::PERMISSIONS]);
        });

        return $workflow;
    }

    public function update(Entity $workflow, array $input)
    {
        $workflow->edit($input);

        $permissionIds = $this->getPermissionIds($workflow, $input[Entity::PERMISSIONS]);

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $permissionIds)
        {
            $this->repo->saveOrFail($workflow);

            $workflow->permissions()->sync($permissionIds);
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

    protected function getPermissionIds(Entity $workflow, array $permissions = [])
    {
        $permissionIds = $workflow->permissions
                                  ->map(function($permission) {
                                        return $permission->getId();
                                    })
                                  ->toArray();

        return array_unique(array_merge($permissionIds, $permissions));
    }
}
