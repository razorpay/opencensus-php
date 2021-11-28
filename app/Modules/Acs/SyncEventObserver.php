<?php

namespace RZP\Modules\Acs;

use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;

class SyncEventObserver
{
    const ACS_OUTBOX_JOB_NAME       = 'acs.sync_account.v1';
    const CREDCASE_OUTBOX_JOB_NAME  = 'credcase.sync_account.v1';

    public static function existsOutboxJob(string $outboxJob = null): bool
    {
        return in_array($outboxJob,
            [self::ACS_OUTBOX_JOB_NAME, self::CREDCASE_OUTBOX_JOB_NAME], true);
    }

    /**
     * Listen to the created event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function created(PublicEntity $entity)
    {
        $outboxJobs = [self::ACS_OUTBOX_JOB_NAME];
        if ($entity->getEntityName() == Entity::MERCHANT && $entity->getConnectionName() == Mode::LIVE) {
            array_push($outboxJobs, self::CREDCASE_OUTBOX_JOB_NAME);
        }
        event(new RecordSyncEvent($entity, $outboxJobs));
    }

    /**
     * Listen to the updated event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function updated(PublicEntity $entity)
    {
        event(new RecordSyncEvent($entity, [self::ACS_OUTBOX_JOB_NAME]));
    }

    /**
     * Listen to the deleted event.
     *
     * @param  PublicEntity $entity
     * @return void
     */
    public function deleted(PublicEntity $entity)
    {
        event(new RecordSyncEvent($entity, [self::ACS_OUTBOX_JOB_NAME]));
    }
}
