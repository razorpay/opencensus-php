<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\BasicAuth\Type;
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

        $basicAuth = app('basicauth');
        $dummyEmail = "partnerships-tech@razorpay.com";

        app('trace')->info(
            TraceCode::PARTNER_CONFIG_EVENT_SAVED,
            [
                'entity'     => $entity->toArray(),
                'route_type' => $basicAuth->getAuthType()
            ]
        );

        $actor = $this->getActorDetails($basicAuth);

        $params = [
            'entity'        => $entity->toArray(),
            'entity_name'   => $entity->getEntityName(),
            'actor_id'      => $actor !== null ? $actor->getId() : "100000Razorpay",
            'actor_email'   => $actor !== null ? ( $actor->getEmail() ?? $dummyEmail ) : $dummyEmail
        ];

        PartnerConfigAuditLogger::dispatch($params, $basicAuth->getMode());
    }

    private function getActorDetails($basicAuth)
    {
        $authType = $basicAuth->getAuthType();

        if ($authType === Type::PRIVILEGE_AUTH)
        {
            return $basicAuth->getAdmin();
        }
        elseif ($authType === Type::PRIVATE_AUTH)
        {
            return $basicAuth->isAdmin() ? $basicAuth->getAdmin() : $basicAuth->getMerchant();
        }

        return null;
    }
}
