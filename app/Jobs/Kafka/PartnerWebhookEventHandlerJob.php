<?php

namespace RZP\Jobs\Kafka;

use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\EntityOrigin\Entity;
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
            $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_INVALID_DATA);

            return;
        }

        $entityOrigin = $this->repoManager->entity_origin->fetchByEntityTypeAndEntityId($input[Entity::ENTITY_TYPE], $input[Entity::ENTITY_ID]);

        if (!empty($entityOrigin) && $entityOrigin->getOriginId() === $input[EntityOriginConstants::APPLICATION_ID])
        {
            $event = $this->getEventData($input, $entityOrigin->getOriginId());

            (new Stork())->processOwnerEvent($event);

            $this->trace->info(TraceCode::PARTNER_WEBHOOK_CALLBACK_EVENT_PROCESSED, [
                'entity_type' => $input[Entity::ENTITY_TYPE],
                'entity_id' => $input[Entity::ENTITY_ID],
                'origin_id' => $input[EntityOriginConstants::APPLICATION_ID],
            ]);
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

    private function getEventData(array $payload, string $ownerId)
    {
        $event = $payload['event'];

        $event['id'] = UniqueIdEntity::generateUniqueId();

        $event['owner_id'] = $ownerId;

        return $event;
    }
}
