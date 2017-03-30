<?php

namespace RZP\Models\Workflow;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Role;
use RZP\Models\Workflow\Step;
use RZP\Models\Admin\Permission;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $workflow = (new Entity)->generateId();

        $workflow->build($input);

        $this->validateExistingWorkflows($input);

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

    protected function validateExistingWorkflows(array $input)
    {
        $permissions = $input[Entity::PERMISSIONS];

        $workflows = $this->repo
                          ->workflow
                          ->fetchWorkflowsWithStepsByPermissions($permissions);

        $minLevelFromInput = $this->getMinLevelFromInput($input);

        foreach ($workflows as $workflow)
        {
            $minLevelFromSteps = $this->getMinLevelFromSteps($workflow->steps);

            if ($minLevelFromSteps === $minLevelFromInput)
            {
                throw new Exception\BadRequestException(
                    Error\ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS);
            }
        }
    }

    protected function getMinLevelFromInput(array $input)
    {
        $minLevel = 0;

        foreach ($input[Entity::STEPS] as $step)
        {
            $minLevel = ($minLevel > $step[Step\Entity::LEVEL]) ? $step[Step\Entity::LEVEL] : $minLevel;
        }

        return $minLevel;
    }

    protected function getMinLevelFromSteps($steps)
    {
        $minLevel = 0;

        foreach ($steps as $step)
        {
            $minLevel = $minLevel > $step->getLevel() ? $step->getLevel() : $minLevel;
        }

        return $minLevel;
    }
}
