<?php

namespace RZP\Models\Internal;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::INTERNAL;

    public function fetchByEntityIDAndType($entityId, $entityType)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->first();
    }
}
