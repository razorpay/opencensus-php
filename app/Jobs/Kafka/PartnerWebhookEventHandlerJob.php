<?php

namespace RZP\Jobs\Kafka;

use App;
use RZP\Events\Event;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use RZP\Models\EntityOrigin\Core;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\EntityOrigin\Entity;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Services\Partnerships\PartnershipsService;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;
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

        $this->setModeFromPayload($input);

        try
        {
            $partnerId = $this->getPartnerIdFromAppId($input[EntityOriginConstants::APPLICATION_ID]);

            if (empty($partnerId))
            {
                return;
            }

            $isExpEnabled = $this->isTransactionIsolationExpEnabledForPartnerApp($partnerId, $this->getEventNameFromPayload($input));

            if ($isExpEnabled)
            {
                $applicationId = (new Core())->getOriginForEntity($input[Entity::ENTITY_TYPE], $input[Entity::ENTITY_ID], $partnerId);

                if (!empty($applicationId) && $applicationId === $input[EntityOriginConstants::APPLICATION_ID])
                {
                    $this->handleEvent($input, $applicationId, $partnerId);
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
                $this->handleEvent($input, $input[EntityOriginConstants::APPLICATION_ID], $partnerId);
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

    private function handleEvent(array $input, string $applicationId, string $partnerId)
    {
        $event = $this->getEventData($input, $applicationId);

        $event['payload'] = $this->handleSensitiveData($event, $partnerId);

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

    private function handleSensitiveData(array $event, string $partnerId)
    {
        $isExpEnabled = $this->isRestrictPIIExpEnabledForPartner($partnerId);

        $payload = $event['payload'];

        $eventName = $event['name'];

        if ($isExpEnabled && $eventName == WebhookEvent::ORDER_PAID)
        {
            $result = (new PartnershipsService())->fetchMaskedData($payload, $partnerId, $eventName);

            return $result['response']['masked_data'];
        }

        return $payload;
    }

    private function getPartnerIdFromAppId(string $applicationId) : string
    {
        $application = $this->repoManager->merchant_application->fetchMerchantApplication($applicationId, MerchantConstants::APPLICATION_ID);

        // For omni, merchant_id is sent as application_id. Needs change in stork to skip sending these messages
        if (empty($application) || empty($application->get(0)))
        {
            $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_APP_NOT_FOUND, ['application_id' => $applicationId]);

            $this->trace->count(Metric::PARTNER_CALLBACK_EVENTS_APP_NOT_FOUND_FAILURE_TOTAL);

            return "";
        }

        return $application->get(0)->getMerchantId();
    }

    private function isTransactionIsolationExpEnabledForPartnerApp(string $partnerId, string $eventName) : bool
    {
        $app = App::getFacadeRoot();

        $experimentName = PartnerConstants::$transactionIsolationEventToExperimentMap[$eventName];

        $properties = [
            'id' => $partnerId,
            'experiment_id' => $app['config']->get($experimentName)
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    private function isRestrictPIIExpEnabledForPartner(string $partnerId) : bool
    {
        $app = App::getFacadeRoot();

        $properties = [
            'id' => $partnerId,
            'experiment_id' => $app['config']->get('app.restrict_pii_data_access_experiment_id')
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    public function setModeFromPayload(array $payload)
    {
        $app = App::getFacadeRoot();

        $currentMode = $this->getModeFromPayload($payload);

        if ($this->mode != $currentMode)
        {
            $app['basicauth']->setModeAndDbConnection($currentMode);

            $this->mode = $currentMode;
        }
    }

    private function getModeFromPayload(array $payload) : string
    {
        $service = $payload['event']['service'] ?? "";

        return str_contains($service, Mode::TEST) ? Mode::TEST : Mode::LIVE;
    }

    private function getEventNameFromPayload(array $payload) : string
    {
        return $payload['event']['name'] ?? "";
    }
}
