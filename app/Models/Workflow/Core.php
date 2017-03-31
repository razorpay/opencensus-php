<?php

namespace RZP\Models\Workflow;

use RZP\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Base;
use RZP\Models\Admin\Permission;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = (new Entity)->generateId();

        $workflow->build($input);

        $minLevel = $this->getMinLevelFromSteps($input[Entity::STEPS]);

        $this->validateExistingWorkflows($input[Entity::PERMISSIONS], $minLevel);

        // Create the steps and workflow in a single transaction
        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            $workflow->permissions()->sync($input[Entity::PERMISSIONS]);

            foreach ($input[Entity::STEPS] as $step)
            {
                $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                Role\Entity::verifyIdAndStripSign($step[Step\Entity::ROLE_ID]);

                (new Step\Core)->create($step);
            }
        });

        return $workflow;
    }

    public function update(Entity $workflow, array $input)
    {
        $workflow->edit($input);

        $permissionIds = $this->getPermissionIds($workflow, $input[Entity::PERMISSIONS]);

        $minLevel = $this->getMinLevelFromSteps($workflow->steps);

        $this->validateExistingWorkflows($input[Entity::PERMISSIONS] ,$minLevel);

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $permissionIds)
        {
            $this->repo->saveOrFail($workflow);

            $workflow->permissions()->sync($permissionIds);
        });

        return $workflow;
    }

    public function delete(Entity $workflow)
    {
        $openWorkflows = (new Action\Core)->fetchOpenWorkflows($workflow->getId());

        if (count($openWorkflows) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED);
        }

        $this->repo->workflow->deleteOrFail($workflow);

        return $workflow;
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
