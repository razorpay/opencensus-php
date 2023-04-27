<?php

namespace RZP\Modules\Acs;

use RZP\Models\Base\PublicEntity;

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
        event(new RecordSyncEvent($entity, [self::ACS_OUTBOX_JOB_NAME, self::CREDCASE_OUTBOX_JOB_NAME]));
    }

    /**
     * Listen to the Rollback Event on created operation
     * @param PublicEntity $entity
     * @return void
     */
    public function afterRollbackCreated(PublicEntity $entity)
    {
        event(new RollbackEvent($entity, 'afterRollback.created'));
    }

    public function afterCommitCreated(PublicEntity $entity)
    {
        event(new CommitEvent($entity, 'afterCommit.created'));
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
     * Listen to the Rollback Event on updated operation
     * @param PublicEntity $entity
     * @return void
     */
    public function afterRollbackUpdated(PublicEntity $entity)
    {
        event(new RollbackEvent($entity, 'afterRollback.updated'));
    }

    public function afterCommitUpdated(PublicEntity $entity)
    {
        event(new CommitEvent($entity, 'afterCommit.updated'));
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

    /**
     * Listen to the Rollback Event on deleted operation
     * @param PublicEntity $entity
     * @return void
     */
    public function afterRollbackDeleted(PublicEntity $entity)
    {
        event(new RollbackEvent($entity, 'afterRollback.deleted'));
    }

    public function afterCommitDeleted(PublicEntity $entity)
    {
        event(new CommitEvent($entity, 'afterCommit.deleted'));
    }
}
