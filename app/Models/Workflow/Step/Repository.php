<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_step';

    protected $adminFetchParamRules = [
        Entity::WORKFLOW_ID   => 'sometimes|string|max:14',
        Entity::ROLE_ID       => 'sometimes|string|max:14',
        Entity::PERMISSION_ID => 'sometimes|string|max:14',
        Entity::LEVEL         => 'sometimes|integer|max:14',
    ];

    public function getNumCheckers(string $workflowId)
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->sum(Entity::REVIEWER_COUNT);
    }

    public function findByLevelAndWorkflowId(
        integer $level,
        string $workflowId,
        $columns = array('*'))
    {
        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->where(Entity::LEVEL, '=', $level)
                    ->get($columns);

    }

    public function getNumCheckersByLevelAndWorkflowId(integer $level, string $workflowId)
    {
        return $this->newQuery()
                    ->where(Entity::LEVEL, '=', $level)
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->sum(Entity::REVIEWER_COUNT);
    }
}
