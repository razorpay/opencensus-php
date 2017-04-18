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

        $workflow->getValidator()->validatePermissionHasOneWorkflow(
            $input[Entity::PERMISSIONS]);

        $workflow->build($input);

        // $minLevel = $this->getMinLevelFromSteps($input[Entity::STEPS]);
        // $this->validateExistingWorkflows($input[Entity::PERMISSIONS], $minLevel);

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            // 1. Create a workflow
            $this->repo->saveOrFail($workflow);

            // 2. Sync its permissions
            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            // 3. Create its steps
            foreach ($input[Entity::STEPS] as $step)
            {
                $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                Role\Entity::verifyIdAndSilentlyStripSign($step[Step\Entity::ROLE_ID]);

                (new Step\Core)->create($step);
            }
        });

        $id = $workflow->getPublicId();
        $orgId = $this->app['basicauth']->getAdminOrgId();

        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow;
    }

    public function update(Entity $workflow, array $input)
    {
        if ($this->isWorkflowEditable($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED);
        }

        $workflow->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            if (empty($input[Entity::STEPS]) === false)
            {
                $workflow->steps()->delete();

                foreach ($input[Entity::STEPS] as $step)
                {
                    $step[Step\Entity::WORKFLOW_ID] = $workflow->getId();

                    Role\Entity::verifyIdAndSilentlyStripSign($step[Step\Entity::ROLE_ID]);

                    (new Step\Core)->create($step);
                }
            }
        });

        $id = $workflow->getPublicId();

        $orgId = $this->app['basicauth']->getAdminOrgId();

        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow;
    }

    public function delete(Entity $workflow)
    {
        if ($this->isWorkflowEditable($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_DELETE_NOT_ALLOWED);
        }

        $this->repo->workflow->deleteOrFail($workflow);

        return $workflow;
    }

    protected function getPermissionIds(Entity $workflow, array $permissions = [])
    {
        $permissionIds = $workflow->permissions->getRelatedIds()->toArray();

        return array_unique(array_merge($permissionIds, $permissions));
    }

    public function isWorkflowEditable(Entity $workflow)
    {
        $actions = $this->repo
                        ->workflow_action
                        ->fetchOpenActionsByWorkflowId($workflow->getId())
                        ->toArray();

        return (empty($actions) === true);
    }
}
