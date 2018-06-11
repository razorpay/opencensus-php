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

        // Check if the permissions given are enabled to have workflows
        $workflow->getValidator()->validatePermissionsForOrg(
            $input[Entity::ORG_ID], $input[Entity::PERMISSIONS]);

        // Check if passed permissions already have a workflow assigned to them
        $workflow->getValidator()->validatePermissionHasOneWorkflow(
            $input[Entity::ORG_ID], $input[Entity::PERMISSIONS]);

        $org = $this->repo->org->findOrFailPublic($input[Entity::ORG_ID]);

        $workflow->org()->associate($org);

        $workflow->build($input);

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            // Create the workflow steps
            foreach ($input[Entity::LEVELS] as $level)
            {
                $step = $this->createStepsForWorkflow($level, $workflow);
            }
        });

        $id = $workflow->getPublicId();
        $orgId = $this->app['basicauth']->getAdminOrgId();

        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow;
    }

    protected function createStepsForWorkflow(array $level, Entity $workflow)
    {
        $steps = $level[Entity::STEPS];

        $data = [
            Step\Entity::WORKFLOW_ID => $workflow->getId(),
            Step\Entity::LEVEL       => $level[Step\Entity::LEVEL],
            Step\Entity::OP_TYPE     => $level[Step\Entity::OP_TYPE],
        ];

        foreach ($steps as $step)
        {
            $step = array_merge($step, $data);

            Role\Entity::verifyIdAndSilentlyStripSign($step[Step\Entity::ROLE_ID]);

            (new Step\Core)->create($step, $workflow);
        }
    }

    public function update(Entity $workflow, array $input)
    {
        $validator = $workflow->getValidator();

        if ($this->workflowHasOpenActions($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED);
        }

        // Check if selected permissions have workflows enabled
        // for them in the current org
        $validator->validatePermissionsForOrg(
            $workflow->getOrgId(), $input[Entity::PERMISSIONS]);

        // Check if passed permissions already have a workflow assigned to them
        $workflow->getValidator()->validatePermissionHasOneWorkflow(
            $workflow->getOrgId(), $input[Entity::PERMISSIONS], $workflow->getId());

        $workflow->edit($input);

        if (empty($input[Entity::ORG_ID]) === false)
        {
            $org = $this->repo->org->findOrFailPublic($input[Entity::ORG_ID]);

            $workflow->org()->associate($org);
        }

        $this->repo->transactionOnLiveAndTest(function() use ($workflow, $input)
        {
            $this->repo->saveOrFail($workflow);

            $this->repo->sync($workflow, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);

            // If levels are passed to the edit function, delete the old steps
            // and create the new ones. Dashboard finds it harder to update the
            // existing entitites
            if (empty($input[Entity::LEVELS]) === false)
            {
                $currentWorkflowSteps = $workflow->load(['steps', 'steps.checkers'])->steps;

                // Check if an action (action_checker entry) has ever been performed
                // on any of the steps.
                //
                // If yes, then soft delete all steps
                // If no, then force delete all steps

                $checkerCount = 0;

                foreach ($currentWorkflowSteps as $step)
                {
                    $checkerCount = $step->checkers->count();

                    if ($checkerCount > 0)
                    {
                        break;
                    }
                }

                if ($checkerCount > 0)
                {
                    // Soft delete
                    $workflow->steps()->delete();
                }
                else
                {
                    // Force delete
                    foreach ($currentWorkflowSteps as $step)
                    {
                        $step->forceDelete();
                    }
                }

                // Create its steps
                foreach ($input[Entity::LEVELS] as $level)
                {
                    $step = $this->createStepsForWorkflow($level, $workflow);
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
        if ($this->workflowHasOpenActions($workflow) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED);
        }

        $this->repo->workflow->deleteOrFail($workflow);

        return $workflow;
    }

    protected function getPermissionIds(Entity $workflow, array $permissions = [])
    {
        $permissionIds = $workflow->permissions->allRelatedIds()->toArray();

        return array_unique(array_merge($permissionIds, $permissions));
    }

    public function workflowHasOpenActions(Entity $workflow)
    {
        $actions = $this->repo
                        ->workflow_action
                        ->fetchOpenActionsByWorkflowId($workflow->getId())
                        ->toArray();

        return (empty($actions) === true);
    }
}
