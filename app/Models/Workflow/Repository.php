<?php

namespace RZP\Models\Workflow;

use RZP\Constants\Table;
use RZP\Models\Workflow\Step;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function findByOrgId(string $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }

    public function fetchWorkflowsByPermissionsAndOrgId(string $permissionId, string $orgId, array $relations = [])
    {
        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, Entity::ID, '=', 'workflow_permissions.workflow_id')
                    ->with($relations)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where('workflow_permissions.permission_id', $permissionId)
                    ->get();
    }

    public function fetchWorkflowsWithStepsByPermissions(array $permissionIds, array $options = [])
    {
        if (isset($options[Step\Entity::WORKFLOW_ID]) === false)
        {
            $options[Step\Entity::WORKFLOW_ID] = [];
        }

        /*
            $workflows:

            SELECT *
            FROM workflows
            JOIN workflow_permissions ON workflow.id = workflow_permissions.workflow_id
            WHERE workflow_permissions.permission_id IN ($permissionIds)

            $workflow->steps:

            SELECT *
            FROM workflow_steps
            WHERE workflow_steps.id IN ($workflowIds)

            $workflow->permissions:

            SELECT *
            FROM workflow_permissions
            WHERE workflow_permissions.id IN ($workflowIds)
        */

        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, Entity::ID, '=', 'workflow_permissions.workflow_id')
                    ->with([Entity::STEPS, Entity::PERMISSIONS])
                    ->whereIn('workflow_permissions.permission_id', $permissionIds)
                    ->whereNotIn(Entity::ID, $options[Step\Entity::WORKFLOW_ID])
                    ->get();
    }

    public function fetchWorkflow(Step\Entity $step)
    {
        if ($step->hasRelation(Step\Entity::WORKFLOW))
        {
            return $step->workflow;
        }

        $workflowId = $step->getWorkflowId();

        $workflow = $this->findOrFail($workflowId);

        $step->workflow()->associate($workflow);

        return $workflow;
    }

    public function getWorkflowIdsForPermissionsAndOrgId(
        string $orgId, array $permissionIds)
    {
        $pid = 'workflow_permissions.permission_id';

        $wOrgId = $this->dbColumn(Entity::ORG_ID);
        $wid = $this->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, $wid, '=', 'workflow_permissions.workflow_id')
                    ->where($wOrgId, '=', $orgId)
                    ->whereIn($pid, $permissionIds)
                    ->whereNull(Entity::DELETED_AT)
                    ->pluck(Entity::ID);
    }
}
