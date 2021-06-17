<?php

namespace RZP\Modules\Acs;

use RZP\Models\Base\PublicEntity;

class SyncEventObserver
{
    /**
     * Listen to the created event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function created(PublicEntity $entity)
    {
        event(new RecordSyncEvent($entity->getMerchantId(), $entity->getConnectionName()));
    }

    /**
     * Listen to the updated event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function updated(PublicEntity $entity)
    {
        event(new RecordSyncEvent($entity->getMerchantId(), $entity->getConnectionName()));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function deleted(PublicEntity $entity)
    {
        event(new RecordSyncEvent($entity->getMerchantId(), $entity->getConnectionName()));
    }
}
