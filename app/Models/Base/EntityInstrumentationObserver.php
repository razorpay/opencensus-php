<?php

namespace RZP\Models\Base;

use RZP\Constants\Metric;
use RZP\Events\EntityInstrumentationEvent;

class EntityInstrumentationObserver
{
    /**
     * Listen to the retrieved event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function retrieved(Entity $entity)
    {
        event(new EntityInstrumentationEvent(Metric::ENTITY_RETRIEVED, $entity->getEntityName()));
    }

    /**
     * Listen to the created event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function created(Entity $entity)
    {
        event(new EntityInstrumentationEvent(Metric::ENTITY_CREATED, $entity->getEntityName()));
    }

    /**
     * Listen to the updated event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function updated(Entity $entity)
    {
        event(new EntityInstrumentationEvent(Metric::ENTITY_UPDATED, $entity->getEntityName()));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function deleted(Entity $entity)
    {
        event(new EntityInstrumentationEvent(Metric::ENTITY_DELETED, $entity->getEntityName()));
    }
}
