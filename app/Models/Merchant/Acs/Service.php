<?php

namespace RZP\Models\Merchant\Acs;

use RZP\Constants\Mode;
use RZP\Jobs\TriggerAcsFullSync;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Modules\Acs\RecordSyncEvent;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function triggerSync(array $input): array
    {
        $this->trace->info(TraceCode::ACS_TRIGGER_SYNC, $input);

        $mode = $input['mode'] ?? Mode::LIVE;

        // if account ids present, trigger sync only for those ids
        if (empty($input['account_ids']) === false)
        {
            foreach ($input['account_ids'] as $id)
            {
                $entity = (new Merchant\Entity)->setConnection($mode)->setId($id);
                event(new RecordSyncEvent($entity));
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