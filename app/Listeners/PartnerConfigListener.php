<?php

namespace RZP\Listeners;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\BasicAuth\Type;
use RZP\Models\Partner\Config;
use RZP\Constants\Environment;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PartnerConfigAuditLogger;
use RZP\Models\Partner\Config\Constants;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Partner\Config\Entity as PartnerConfigEntity;

/**
 * PartnerConfigListener listens to Partner\Config\Entity's events
 */
class PartnerConfigListener
{
    const NON_PASSABLE_ENVIRONMENTS = [Environment::TESTING, Environment::BVT];

    public function onSaved(Config\EventSaved $event)
    {
        $entity = $event->entity;

        try
        {
            $isExpEnabled = $this->isAuditingExpEnabled($entity);

            if($isExpEnabled === false or $entity->getConnectionName() !== Mode::LIVE or
                in_array(app('env'), self::NON_PASSABLE_ENVIRONMENTS, true) === true)
            {
                return ;
            }

            $basicAuth = app('basicauth');

            app('trace')->info(
                TraceCode::PARTNER_CONFIG_EVENT_SAVED,
                [
                    'entity'     => $entity->toArrayAudit(),
                    'route_type' => $basicAuth->getAuthType()
                ]
            );

            $actor = $this->getActorDetails($basicAuth);

            PartnerConfigAuditLogger::dispatch($this->toParams($entity, $actor), $basicAuth->getMode());
        }
        catch(\Throwable $e)
        {
            app('trace')->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PARTNER_CONFIG_AUDIT_JOB_ERROR,
                [ 'message' => $e->getMessage() ]
            );
            app('trace')->count(PartnerMetric::PARTNER_CONFIG_AUDIT_FAIL);
        }
    }

    private function toParams($entity, $actor)
    {
        $dummyEmail = "partnerships-tech@razorpay.com";

        return [
            'entity'        => $entity->toArrayAudit(),
            'entity_name'   => $entity->getEntityName(),
            'actor_id'      => $actor !== null ? $actor->getId() : "100000Razorpay",
            'actor_email'   => $actor !== null ? ( $actor->getEmail() ?? $dummyEmail ) : $dummyEmail
        ];
    }

    private function isAuditingExpEnabled($entity)
    {
        $properties = [
            'id'            => $entity[PartnerConfigEntity::ENTITY_TYPE] === Constants::APPLICATION ?
                $entity[PartnerConfigEntity::ENTITY_ID] : $entity[PartnerConfigEntity::ORIGIN_ID],
            'experiment_id' => app('config')->get('app.partner_config_auditing_experiment_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
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
            return $basicAuth->isAdminAuth() ? $basicAuth->getAdmin() : $basicAuth->getMerchant();
        }

        return null;
    }
}
