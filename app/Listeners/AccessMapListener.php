<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\AccessMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PartnerConfigAuditLogger;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Partner\Metric as PartnerMetric;

/**
 * AccessMapListener listens to AccessMap\Entity's events.
 *
 * Example usage- Stork has cached copy of this mapping and in this handler invalidation is triggerred.
 */
class AccessMapListener extends BaseListener
{
    const NON_PASSABLE_ENVIRONMENTS = [Environment::TESTING, Environment::BVT];

    public function onSaved(AccessMap\EventSaved $event)
    {
        $entity = $event->entity;

        $appId = $entity[AccessMap\Entity::ENTITY_ID];

        $this->trace->info(TraceCode::ACCESS_MAP_EVENT_SAVED, $this->getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());
        try
        {
            if(
                $entity->getConnectionName() === Mode::LIVE
                && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
            )
            {
                PartnerConfigAuditLogger::dispatch($this->getAuditLogParams($entity), Mode::LIVE);
                if((new PartnerCore())->isPartnerEntitySyncExpEnabled($appId))
                {
                    // call partnership service to sync entity
                    app('partnerships')->upsertMerchantAccessMap(['merchant_access_map' => $entity->toArray()]);
                }
            }
        }
        catch(\Throwable $e)
        {

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ACCESS_MAP_SYNC_ERROR,
                [ 'message' => $e->getMessage(), 'entity'=> $entity->toArray() ]
            );
            $this->trace->count(PartnerMetric::MERCHANT_ACCESS_MAP_SYNC_FAILED);
        }
    }

    public function onDeleted(AccessMap\EventDeleted $event)
    {
        $entity = $event->entity;

        $appId = $entity[AccessMap\Entity::ENTITY_ID];

        $this->trace->info(TraceCode::ACCESS_MAP_EVENT_DELETED, $this->getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());

        try
        {
            if(
            $entity->getConnectionName() === Mode::LIVE
            && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
            )
            {
                PartnerConfigAuditLogger::dispatch($this->getAuditLogParams($entity), Mode::LIVE);
                if((new PartnerCore())->isPartnerEntitySyncExpEnabled($appId))
                {
                    // call partnership service to sync entity
                    app('partnerships')->deleteMerchantAccessMap(['id' => $entity->getId() ]);
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ACCESS_MAP_SYNC_ERROR,
                [ 'message' => $e->getMessage(), 'entity'=> $entity->toArray() ]
            );
            $this->trace->count(PartnerMetric::MERCHANT_ACCESS_MAP_SYNC_FAILED);
        }
    }

    private function getTraceInfo(AccessMap\Entity $entity): array
    {
        $fields = [
            AccessMap\Entity::ID,
            AccessMap\Entity::MERCHANT_ID,
            AccessMap\Entity::ENTITY_ID,
            AccessMap\Entity::ENTITY_TYPE,
            AccessMap\Entity::ENTITY_OWNER_ID,
        ];

        return array_merge(
            [
                'entity' => $entity->only($fields),
            ],
            $this->getActor(),
        );
    }

    private function getAuditLogParams(AccessMap\Entity $entity): array
    {
        // get route name from request context if available else get job name from worker context
        $routeName = '';
        if(empty($this->request->route()) == false)
        {
            $routeName = $this->request->route()->getName();
        }
        else if ( empty(app('worker.ctx')) == false)
        {
            $routeName = app('worker.ctx')->getJobName();
        }
        return array_merge(
            [
                'entity'        => $entity->toArrayAudit(),
                'entity_name'   => $entity->getEntityName(),
                'meta_data'     => [
                    'auth_type'  => $this->ba->getAuthType(),
                    'route_name' => $this->request->route()->getName(),
                ],
            ],
            $this->getActor(),
        );
    }
}
