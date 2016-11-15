<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'file_store';

    public function getByParams($id, $merchantId, $entityId, $entityType, $type)
    {
        $query = $this->newQuery();

        if ($id !== null)
        {
            $query->where(Entity::ID, '=', $id);
        }
        if ($type !== null)
        {
            $query->where(Entity::TYPE, '=', $type);
        }
        if ($entityId !== null)
        {
            $query->where(Entity::ENTITY_ID, '=', $entityId);
        }
        if ($entityType !== null)
        {
            $query->where(Entity::ENTITY_TYPE, '=', $entityType);
        }

 //       $query->where(Entity::MERCHANT_ID, '=', $merchantId);

        return $query->get();
    }
}
