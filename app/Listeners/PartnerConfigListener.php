<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Config;
use RZP\Constants\Environment;
use RZP\Jobs\PartnerConfigAuditLogger;

/**
 * PartnerConfigListener listens to Partner\Config\Entity's events
 */
class PartnerConfigListener
{
    const NON_PASSABLE_ENVIRONMENTS = [Environment::TESTING, Environment::BVT];

    public function onSaved(Config\EventSaved $event)
    {
        $entity = $event->entity;

        if($entity->getConnectionName() !== Mode::LIVE or
           in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === true)
        {
            return ;
        }

        app('trace')->info(TraceCode::PARTNER_CONFIG_EVENT_SAVED,
           [
               'entity'     => $entity->toArray(),
           ]);

        $basicAuth = app('basicauth');
        $isAdminAuth = $basicAuth->isAdminAuth();

        $params = [
            'entity'        => $entity->toArray(),
            'entity_name'   => $entity->getEntityName(),
            'actor_id'      => $isAdminAuth ? $basicAuth->getAdmin()->getId() : "",
            'actor_email'   => $isAdminAuth ? $basicAuth->getAdmin()->getEmail() : ""
        ];

        PartnerConfigAuditLogger::dispatch($params, $basicAuth->getMode());
    }
}
