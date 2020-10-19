<?php

namespace RZP\Models\Workflow\Service\EntityMap;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_entity_map';

    public function getByWorkflowId($workflowId) : Entity
    {
        $workflowIdColumn           = $this->dbColumn(Entity::WORKFLOW_ID);

        return $this->newQuery()
                    ->where($workflowIdColumn, '=', $workflowId)
                    ->firstOrFail();
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

    public function isPresent(string $entityType, string $entityId)
    {
        $workflowEntity = $this->findByEntityIdAndEntityType($entityType, $entityId);

        return empty($workflowEntity) === false;
    }
}
