<?php

namespace RZP\Jobs\Kafka;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use RZP\Models\EntityOrigin\Core;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\EntityOrigin\Entity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\EntityOrigin\Constants as EntityOriginConstants;
use RZP\Models\Merchant\WebhookV2\Stork;

class PartnerWebhookEventHandlerJob extends Job
{
    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            Constants::JOB_ATTEMPTS => $this->attempts(),
            Constants::MODE         => $this->mode,
            Constants::PAYLOAD      => $this->getPayload(),
            Constants::TASK_ID      => $taskId
        ];

        $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_REQUEST_PAYLOAD, $tracePayload);

        if (empty($this->getPayload()))
        {
            $this->trace->count(Metric::PARTNER_CALLBACK_EVENTS_RECEIVED_FAILURE_TOTAL);

            $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_INVALID_DATA);
        }
        else
        {
            $this->processPartnerEvent($this->getPayload());
        }
    }

    private function processPartnerEvent(array $input)
    {
        if (empty($input[Entity::ENTITY_TYPE]) || empty($input[Entity::ENTITY_ID]) || empty($input[EntityOriginConstants::APPLICATION_ID]))
        {
            $this->trace->count(Metric::PARTNER_CALLBACK_EVENTS_RECEIVED_FAILURE_TOTAL);

            $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_INVALID_DATA);

            return;
        }

        try
        {
            $isExpEnabled = $this->isTransactionIsolationExpEnabledForPartnerApp($input[EntityOriginConstants::APPLICATION_ID]);

            if ($isExpEnabled)
            {
                $applicationId = (new Core())->getOriginForEntity($input[Entity::ENTITY_TYPE], $input[Entity::ENTITY_ID]);

                if (!empty($applicationId) && $applicationId === $input[EntityOriginConstants::APPLICATION_ID])
                {
                    $this->handleEvent($input, $applicationId);
                }
                else
                {
                    $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_ENTITY_ORIGIN_NOT_FOUND, [
                        'entity_type' => $input[Entity::ENTITY_TYPE],
                        'entity_id' => $input[Entity::ENTITY_ID],
                        'origin_id' => $input[EntityOriginConstants::APPLICATION_ID],
                    ]);
                }
            }
            else
            {
                $this->handleEvent($input, $input[EntityOriginConstants::APPLICATION_ID]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->count(Metric::PARTNER_CALLBACK_EVENTS_RECEIVED_FAILURE_TOTAL);

            $this->trace->error(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_ERROR, [
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function handleEvent(array $input, string $applicationId)
    {
        $event = $this->getEventData($input, $applicationId);

        (new Stork())->processEventForOwner($event);

        $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_PROCESSED, [
            'entity_type' => $input[Entity::ENTITY_TYPE],
            'entity_id' => $input[Entity::ENTITY_ID],
            'origin_id' => $input[EntityOriginConstants::APPLICATION_ID],
        ]);
    }

    private function getEventData(array $payload, string $ownerId)
    {
        $event = $payload['event'];

        $event['id'] = UniqueIdEntity::generateUniqueId();

        $event['owner_id'] = $ownerId;

        return $event;
    }

    private function isTransactionIsolationExpEnabledForPartnerApp(string $applicationId) : bool
    {
        $app = App::getFacadeRoot();

        $application = $this->repoManager->merchant_application->fetchMerchantApplication($applicationId, MerchantConstants::APPLICATION_ID);

        $properties = [
            'id' => $application->get(0)->getMerchantId(),
            'experiment_id' => $app['config']->get('app.transaction_isolation_for_webhooks_experiment_id')
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }
}
