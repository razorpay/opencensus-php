<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_step';

    protected $adminFetchParamRules = [
        Entity::WORKFLOW_ID   => 'sometimes|string|max:14',
        Entity::ROLE_ID       => 'sometimes|string|max:14',
        Entity::LEVEL         => 'sometimes|integer|max:14',
    ];

    public function findByLevelAndWorkflowId(
        int $level,
        string $workflowId,
        $columns = array('*'))
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->where(Entity::LEVEL, '=', $level)
                    ->get($columns);

    }

    public function getNextLevelOfWorkflowId(int $level, string $workflowId)
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->where(Entity::LEVEL, '>', $level)
                    ->orderBy(Entity::LEVEL)
                    ->first();
    }

    /*
        Find a workflow step definition level, workflow_id and role_id
    */
    public function findByLevelWorkflowIdAndRoleId(
        int $level,
        string $workflowId,
        array $roleIds,
        $columns = array('*'))
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->where(Entity::LEVEL, '=', $level)
                    ->whereIn(Entity::ROLE_ID, $roleIds)
                    ->get($columns);
    }

    public function getLastLevelOfWorkflow(string $workflowId)
    {
        return (int) $this->newQuery()
                          ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                          ->max(Entity::LEVEL);
    }
}
