<?php

namespace RZP\Models\Base;

use App;

use RZP\Events\EntityInstrumentationEvent;

class EntityInstrumentationObserver
{
    const RETRIEVED = 'entity_retrieved';
    const CREATED   = 'entity_created';
    const UPDATED   = 'entity_updated';
    const DELETED   = 'entity_deleted';

    /**
     * Listen to the retrieved event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function retrieved(Entity $entity)
    {
        event(new EntityInstrumentationEvent(self::RETRIEVED, $entity->getEntityName()));
    }

    /**
     * Listen to the created event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function created(Entity $entity)
    {
        event(new EntityInstrumentationEvent(self::CREATED, $entity->getEntityName()));
    }

    /**
     * Listen to the updated event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function updated(Entity $entity)
    {
        event(new EntityInstrumentationEvent(self::UPDATED, $entity->getEntityName()));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function deleted(Entity $entity)
    {
        event(new EntityInstrumentationEvent(self::DELETED, $entity->getEntityName()));
    }
}
