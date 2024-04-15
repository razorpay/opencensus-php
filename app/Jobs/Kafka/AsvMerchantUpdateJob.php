<?php

namespace RZP\Jobs\Kafka;

use RZP\Constants\Entity;
use RZP\Models\Merchant\Core;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Audit\Constants as AuditConstants;

class AsvMerchantUpdateJob extends Job
{
    const MERCHANT_ID = "merchant_id";
    const ENTITY_NAME = "entity_name";

    //List of entities for which audit framework is enabled
    //These are not table names, rather entity names
    const MERCHANT_AUDIT_ENTITIES = ['merchant', 'merchant_business_detail',
                                     'stakeholder', 'merchant_detail',
                                     'merchant_document', 'merchant_website'];


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

        $this->createAuditForEntities();
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
            case Entity::MERCHANT:
                (new Core())->invalidateCache(
                    $updateEventPayload[self::ENTITY_NAME],
                    $updateEventPayload[self::MERCHANT_ID]
                );
                break;
            case Entity::MERCHANT_DETAIL :{
                (new \RZP\Models\Merchant\Detail\Core())->invalidateCache(
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

    protected function createAuditForEntities()
    {
        try
        {
            $updateEventPayload = $this->getMerchantUpdateEvent();
            $auditId            = $this->payload['entity']['audit_id'];

            if (in_array($updateEventPayload[self::ENTITY_NAME], self::MERCHANT_AUDIT_ENTITIES) and empty($auditId) === false)
            {

                $auditInfoEntity = (new \RZP\Models\Base\Audit\Core())->getAuditInfoEntity($auditId);

                if (empty($auditInfoEntity) === false)
                {
                    //If audit info entry already exists, do nothing
                    return;
                }

                $metadataFromPayload = $this->payload['metadata'];
                $meta                = [
                    AuditConstants::ACTOR_ID   => $metadataFromPayload['actor']['id'],
                    AuditConstants::ACTOR_TYPE => $metadataFromPayload['actor']['type'],
                    AuditConstants::AUTH_TYPE  => $metadataFromPayload['auth_type'],
                    AuditConstants::APP        => $metadataFromPayload['app_name'],
                    AuditConstants::IP         => $metadataFromPayload['ip'],
                    AuditConstants::TASK_ID    => $metadataFromPayload['task_id']
                ];

                (new \RZP\Models\Base\Audit\Core())->createWithParams($auditId, $meta);

                $this->trace->info(TraceCode::AUDIT_INFO_CREATED_FOR_ASV_EVENTS, [
                    'merchantId' => $updateEventPayload[self::MERCHANT_ID],
                    'entityName' => $updateEventPayload[self::ENTITY_NAME],
                    'audit_id'   => $auditId
                ]);

            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::AUDIT_INFO_CREATE_FAILURE_FOR_ASV_EVENTS,
                                [
                                    'merchantId' => $this->payload[self::MERCHANT_ID],
                                    'entityName' => $this->payload[self::ENTITY_NAME],
                                    'error'      => $e->getMessage()
                                ]);
        }
    }

}
