<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant\WebhookV2\Stork;

/**
 * AccessMapListener listens to AccessMap\Entity's events.
 *
 * Example usage- Stork has cached copy of this mapping and in this handler invalidation is triggerred.
 */
class AccessMapListener
{
    public function onSaved(AccessMap\EventSaved $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::ACCESS_MAP_EVENT_SAVED, getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());
    }

    public function onDeleted(AccessMap\EventDeleted $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::ACCESS_MAP_EVENT_DELETED, getTraceInfo($entity));

        (new Stork($entity->getConnectionName()))->invalidateAffectedOwnersCache($entity->getMerchantId());
    }
}

function getTraceInfo(AccessMap\Entity $entity): array
{
    $fields = [
        AccessMap\Entity::ID,
        AccessMap\Entity::MERCHANT_ID,
        AccessMap\Entity::ENTITY_ID,
        AccessMap\Entity::ENTITY_TYPE,
        AccessMap\Entity::ENTITY_OWNER_ID,
    ];

    return $entity->only($fields);
}
