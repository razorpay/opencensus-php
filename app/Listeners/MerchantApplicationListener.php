<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Partner\Metric as PartnerMetric;

/**
 * MerchantApplicationListener listens to MerchantApplications\Entity's events.
 *
 */
class MerchantApplicationListener extends BaseListener
{
    const NON_PASSABLE_ENVIRONMENTS = [Environment::TESTING, Environment::BVT];

    public function onSaved(MerchantApplications\EventSaved $event)
    {
        $entity = $event->entity;
        try
        {
            $appId = $entity[MerchantApplications\Entity::APPLICATION_ID];
            $this->trace->info(TraceCode::MERCHANT_APPLICATION_EVENT_SAVED, $this->getTraceInfo($entity));

            if(
                $entity->getConnectionName() === Mode::LIVE
                && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
                && (new PartnerCore())->isPartnerEntitySyncExpEnabled($appId)
            )
            {
                // call partnership service to sync entity
                app('partnerships')->upsertMerchantApplication(['merchant_application' => $entity->toArray()]);
            }
        }
        catch(\Throwable $e)
        {
            app('trace')->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_APPLICATION_SYNC_ERROR,
                [ 'message' => $e->getMessage(), 'entity'=> $entity->toArray() ]
            );
            $this->trace->count(PartnerMetric::MERCHANT_APPLICATION_SYNC_FAILED);
        }
    }

    public function onDeleted(MerchantApplications\EventDeleted $event)
    {
        $entity = $event->entity;
        try
        {
            $appId = $entity[MerchantApplications\Entity::APPLICATION_ID];

            $this->trace->info(TraceCode::MERCHANT_APPLICATION_EVENT_DELETED, $this->getTraceInfo($entity));

            if (
                $entity->getConnectionName() === Mode::LIVE
                && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
                && (new PartnerCore())->isPartnerEntitySyncExpEnabled($appId)
            )
            {
                // call partnership service to sync entity
                app('partnerships')->deleteMerchantApplication(['id' => $entity->getId()]);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_APPLICATION_SYNC_ERROR,
                [ 'message' => $e->getMessage(), 'entity'=> $entity->toArray() ]
            );
            $this->trace->count(PartnerMetric::MERCHANT_APPLICATION_SYNC_FAILED);
        }
    }

    private function getTraceInfo(MerchantApplications\Entity $entity): array
    {
        $fields = [
            MerchantApplications\Entity::ID,
            MerchantApplications\Entity::MERCHANT_ID,
            MerchantApplications\Entity::TYPE,
            MerchantApplications\Entity::APPLICATION_ID,
        ];

        return $entity->only($fields);
    }
}
