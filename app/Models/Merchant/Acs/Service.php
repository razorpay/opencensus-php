<?php

namespace RZP\Models\Merchant\Acs;

use RZP\Constants\Mode;
use RZP\Jobs\TriggerAcsFullSync;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Modules\Acs\RecordSyncEvent;
use RZP\Modules\Acs\SyncEventObserver;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function triggerSync(array $input): array
    {
        $this->trace->info(TraceCode::ACS_TRIGGER_SYNC, $input);

        $mode = Mode::exists($input['mode']) ? $input['mode'] : Mode::LIVE;

        $outboxJobs = [];
        foreach ($input['outbox_jobs'] as $outboxJob) {
            if (SyncEventObserver::existsOutboxJob($outboxJob)) {
                array_push($outboxJobs, $outboxJob);
            }
        }
        $outboxJobs = empty($outboxJobs) ? [SyncEventObserver::ACS_OUTBOX_JOB_NAME] : $outboxJobs;

        // if account ids present, trigger sync only for those ids
        if (empty($input['account_ids']) === false)
        {
            foreach ($input['account_ids'] as $id)
            {
                // TODO: should validate if input account_id is present in DB, If yes, create a new event
                $entity = (new Merchant\Entity)->setConnection($mode)->setId($id);
                event(new RecordSyncEvent($entity, $outboxJobs));
            }
        }

        return [];
    }

    public function triggerFullSync(array $input)
    {
        TriggerAcsFullSync::dispatch($this->mode, $input);

        return [];
    }
}
