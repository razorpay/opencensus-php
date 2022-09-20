<?php

namespace RZP\Models\Merchant\Acs;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Jobs\TriggerAcsFullSync;
use RZP\Exception\LogicException;
use RZP\Modules\Acs\RecordSyncEvent;
use RZP\Modules\Acs\SyncEventObserver;
use RZP\Models\Merchant\Acs\EventProcessor\EventProcessorFactory;

class Service extends Base\Service
{

    /**
     * @var EventProcessorFactory
     */
    public $eventProcessorFactory;

    public function __construct()
    {
        parent::__construct();

        $this->eventProcessorFactory = new EventProcessorFactory();
    }

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
        if (empty($input['account_ids']) === false) {
            foreach ($input['account_ids'] as $id) {
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

    /**
     * Generic handler for Account and Related entity update event
     *
     * @param array $input
     * @throws LogicException
     */
    public function handleAccountUpdateEvent(array $input)
    {
        $this->trace->info(TraceCode::ACS_ENTITY_UPDATE_EVENT, $input);
        if ((array_key_exists('message', $input) === false) || (array_key_exists('data', $input['message']) === false)) {
            $this->trace->info(TraceCode::ACS_ENTITY_UPDATE_EVENT_DATA_NOT_FOUND, $input);
            return;
        }

        $data = json_decode(base64_decode($input['message']['data'], true), true);

        $eventProcessors = $this->eventProcessorFactory->GetEventProcessors();

        foreach ($eventProcessors as $eventProcessor) {
            if ($eventProcessor->ShouldProcess($data) === true) {
                $eventProcessor->Process($data);
            }
        }

        $this->trace->info(TraceCode::ACS_ENTITY_UPDATE_EVENT_HANDLED);
    }
}
