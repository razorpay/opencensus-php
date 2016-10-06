<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'file_handler';

    public function getByEntityIdAndEntityType($entityId, $entityType)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->where(Entity::ENTITY_TYPE, '=', $entityType)
            ->get();
    }
}
