<?php

namespace RZP\Models\Workflow\Service\EntityMap;

use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_entity_map';

    public function getByWorkflowId($workflowId) : Entity
    {
        $workflowIdColumn = $this->dbColumn(Entity::WORKFLOW_ID);

        return $this->newQuery()
                    ->where($workflowIdColumn, '=', $workflowId)
                    ->firstOrFail();
    }

    // If db records are not found, should not return exception
    public function getByWorkflowIdByFirst($workflowId)
    {
        $workflowIdColumn = $this->dbColumn(Entity::WORKFLOW_ID);

        return $this->newQuery()
                    ->where($workflowIdColumn, '=', $workflowId)
                    ->first();
    }

    /**
     * @param string $entityType
     * @param string $entityId
     * @return mixed
     */
    public function findByEntityIdAndEntityType(string $entityType, string $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, '=', $entityType)
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->first();
    }

    public function isPresent(string $entityType, string $entityId): bool
    {
        $workflowEntity = $this->findByEntityIdAndEntityType($entityType, $entityId);

        return empty($workflowEntity) === false;
    }

    public function getPayoutServiceWorkflowEntityMap(string $payoutId)
    {
        $tableName = Table::WORKFLOW_ENTITY_MAP;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where entity_id = '$payoutId' and entity_type = 'payout'");
    }

    public function getPayoutServiceWorkflowEntityMapByWorkflowId(string $workflowId)
    {
        $tableName = Table::WORKFLOW_ENTITY_MAP;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where workflow_id = '$workflowId'");
    }

}
