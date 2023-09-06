<?php

namespace RZP\Jobs\Kafka;

use RZP\Constants\Entity;
use RZP\Models\Merchant\Core;
use RZP\Trace\TraceCode;

class AsvMerchantUpdateJob extends Job
{
    const MERCHANT_ID = "merchant_id";
    const ENTITY_NAME = "entity_name";
    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode,
            'payload' => $this->getPayload(),
            'task_id' => $taskId
        ];

        $this->trace->info(TraceCode::ASV_MERCHANT_UPDATE_EVENTS, $tracePayload);

        $this->invalidateCache();
    }

    protected function invalidateCache(): void
    {

        $updateEventPayload = $this->getMerchantUpdateEvent();

        if (isset($updateEventPayload[self::ENTITY_NAME]) === false or
            isset($updateEventPayload[self::MERCHANT_ID]) === false) {
            $this->trace->warning(TraceCode::INVALID_PAYLOAD_FOR_MERCHANT_UPDATE_EVENTS, [
                "payload" => $this->getPayload()
            ]);
            return;
        }

        switch ($updateEventPayload[self::ENTITY_NAME]) {
            case Entity::MERCHANT: {
                (new Core())->invalidateCache(
                    $updateEventPayload[self::ENTITY_NAME],
                    $updateEventPayload[self::MERCHANT_ID]
                );
                break;
            }
            default: {
                return;
            }
        }
    }

    public function getMerchantUpdateEvent(): array
    {
        return [
            self::MERCHANT_ID => $this->payload[self::MERCHANT_ID],
            self::ENTITY_NAME => $this->payload[self::ENTITY_NAME],
        ];
    }

}
