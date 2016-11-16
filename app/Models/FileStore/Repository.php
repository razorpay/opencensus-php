<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'file_store';

    public function fetchByParams($id, $merchantId, $entityId, $entityType, $type)
    {
        $query = $this->newQuery()
            ->when($id, function ($query) use ($id)
            {
                return $query->where(Entity::ID, $id);
            })
            ->when($merchantId, function ($query) use ($merchantId)
            {
                return $query->where(Entity::MERCHANT_ID, $merchantId);
            })
            ->when($entityId, function ($query) use ($entityId)
            {
                return $query->where(Entity::ENTITY_ID, $entityId);
            })
            ->when($entityType, function ($query) use ($entityType)
            {
                return $query->where(Entity::ENTITY_TYPE, $entityType);
            })
            ->when($type, function ($query) use ($type)
            {
                return $query->where(Entity::TYPE, $type);
            });

        return $query->get();
    }
}
