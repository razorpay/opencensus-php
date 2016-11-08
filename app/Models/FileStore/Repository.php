<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'file';

    public function getByEntityIdAndEntityType($entityId, $entityType)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->where(Entity::ENTITY_TYPE, '=', $entityType)
            ->get();
    }
}
