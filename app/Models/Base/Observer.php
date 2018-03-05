<?php

namespace RZP\Models\Base;

class Observer
{
    /**
     * Used to flush the cache on updates, for entities which are using
     * query cache. We flush the cache by deleting all keys with the tag
     * <entity_name>_<entity_id>
     * @param  PublicEntity $entity
     */
    public function updated(PublicEntity $entity)
    {
        $entity->flushCache($entity->getEntity() . '_' . $entity->getId());
    }
}
