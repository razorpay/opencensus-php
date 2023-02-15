<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_state_map';

    public function getByStateId($stateId)
    {
        $stateIdColumn = $this->dbColumn(Entity::STATE_ID);

        return $this->newQuery()
                    ->where($stateIdColumn, '=', $stateId)
                    ->first();
    }

    public function getByWorkflowId($workflowId)
    {
        $workflowIdColumn = $this->dbColumn(Entity::WORKFLOW_ID);

        return $this->newQuery()
                    ->where($workflowIdColumn, '=', $workflowId)
                    ->get();
    }
}
