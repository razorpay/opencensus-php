<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\AccessMap;
use RZP\Jobs\PartnerConfigAuditLogger;
use RZP\Models\Merchant\WebhookV2\Stork;

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

        $this->trace->info(TraceCode::ACCESS_MAP_EVENT_SAVED, $this->getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());

        if(
            $entity->getConnectionName() === Mode::LIVE
            && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
        )
        {
            PartnerConfigAuditLogger::dispatch($this->getAuditLogParams($entity), Mode::LIVE);
        }
    }

    public function onDeleted(AccessMap\EventDeleted $event)
    {
        $entity = $event->entity;

        $this->trace->info(TraceCode::ACCESS_MAP_EVENT_DELETED, $this->getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());

        if(
            $entity->getConnectionName() === Mode::LIVE
            && in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === false
        )
        {
            PartnerConfigAuditLogger::dispatch($this->getAuditLogParams($entity), Mode::LIVE);
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
