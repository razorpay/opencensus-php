<?php

namespace RZP\Models\Merchant\Acs;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use RZP\Jobs\TriggerAcsFullSync;
use RZP\Exception\LogicException;
use RZP\Jobs\TriggerAsvFullParityCheck;
use RZP\Models\Merchant\Acs\ParityChecker;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\EventProcessor\EventProcessorFactory;

class Service extends Base\Service
{

    /**
     * @var EventProcessorFactory
     */
    public $eventProcessorFactory;
    public $allowedOperations;
    public $splitzHelper;

    public function __construct()
    {
        parent::__construct();

        $this->eventProcessorFactory = new EventProcessorFactory();
        $this->allowedOperations = array('full_sync', 'reverse_map', 'full_sync_and_reverse_map');
        $this->splitzHelper = new SplitzHelper();
    }

    public function triggerSync(array $input): array
    {
        $this->trace->info(TraceCode::ACS_TRIGGER_SYNC, $input);

        $mode = Mode::exists($input['mode']) ? $input['mode'] : Mode::LIVE;
        $operationExists = array_key_exists('operation', $input);
        $operation = ($operationExists === true) ? $input['operation'] : '';

        $id = count($input['account_ids']) > 0 ? $input['account_ids'][0] : $this->app['request']->getId();
        $splitzExperimentId = $this->app['config']['applications.acs.asv_full_data_sync_splitz_experiment_id'];

        $isSPlitzOn = $this->splitzHelper->isSplitzOn($splitzExperimentId, $id);
        $this->trace->info(TraceCode::ASV_FULL_SYNC_SPLITZ_ENABLED, ['splitz_on' => $isSPlitzOn, 'id' => $id, 'operation' => $operation]);

        // If splitz On, operation exists and in the list {full_sync, reverse_mapping} then run full sync or reverse_map via kafka
        if ($isSPlitzOn && $operationExists === true && in_array($input['operation'], $this->allowedOperations) === true) {
            $operation = $input['operation'];
            foreach ($input['account_ids'] as $id) {
                $this->pushDataSyncEventsToKafka($operation, $id);
            }
            return [];
        }

        // If operation doesn't exist then normal flow of full sync via outbox relay
        // Commenting the code for now if needed then can be uncommented

        //        $outboxJobs = [];
        //        foreach ($input['outbox_jobs'] as $outboxJob) {
        //            if (SyncEventObserver::existsOutboxJob($outboxJob)) {
        //                array_push($outboxJobs, $outboxJob);
        //            }
        //        }
        //        $outboxJobs = empty($outboxJobs) ? [SyncEventObserver::ACS_OUTBOX_JOB_NAME] : $outboxJobs;
        //
        //        // if account ids present, trigger sync only for those ids
        //        if (empty($input['account_ids']) === false) {
        //            foreach ($input['account_ids'] as $id) {
        //                // TODO: should validate if input account_id is present in DB, If yes, create a new event
        //                $entity = (new Merchant\Entity)->setConnection($mode)->setId($id);
        //                event(new RecordSyncEvent($entity, $outboxJobs));
        //            }
        //        }
        return [];
    }

    public function triggerFullParityCheck(array $input): array
    {
        $this->trace->info(TraceCode::ASV_TRIGGER_FULL_PARITY_CHECK, $input);

        TriggerAsvFullParityCheck::dispatch($this->mode, $input);

        return [];
    }

    public function triggerParityCheck(array $input): array
    {
        $this->trace->info(TraceCode::ASV_TRIGGER_PARITY_CHECK, $input);
        $merchantIds = $input[Constant::MERCHANT_IDS];
        $parityCheckEntity = $input[Constant::PARITY_CHECK_ENTITY];
        $parityCheckMethods = $input[Constant::PARITY_CHECK_METHODS] ?? [Constant::GET_BY_MERCHANT_ID];
        $parityService = new ParityChecker\Service($merchantIds, $parityCheckEntity, $parityCheckMethods);
        $parityService->triggerParityCheck();
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

    public function pushDataSyncEventsToKafka(string $operation, string $merchant_id)
    {
        $topic = $this->app['config']['applications.acs.asv_data_sync_events_topic'];

        $message = [
            'task_name' => $operation,
            'data' => [
                'merchant_id' => $merchant_id,
            ],
            'meta_data' => [
                'request_id' => $this->app['request']->getId(),
                'task_id' => $this->app['request']->getTaskId()
            ]
        ];

        $this->trace->info(TraceCode::ASV_KAFKA_PUSH_MESSAGE, ['topic' => $topic, 'message' => $message]);

        (new KafkaProducer($topic, stringify($message)))->Produce();
    }
}
