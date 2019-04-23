<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Webhook\Event;

class Service extends Base\Service
{
    protected function getRepository()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }

    public function createFromGatewayDowntimes(array $input)
    {
        return $this->core()->createFromGatewayDowntimes($input);
    }

    public function getMethodDowntimeDataForMerchant(array $input): array
    {
        $downtimes = $this->getRepository()->fetchCurrentAndFutureDowntimes();

        return $downtimes->toArrayPublic();
    }

    public function triggerDowntimes(array $input): array
    {
        $activateResponse = $this->activateDowntimes();

        $resolveResponse = $this->resolveDowntimes();

        return [
            'activated' => $activateResponse,
            'resolved'  => $resolveResponse,
        ];
    }

    protected function activateDowntimes()
    {
        $now = Carbon::now()->getTimestamp();

        $downtimesToActivate = $this->getRepository()->fetchFutureScheduledDowntimesToActivate($now);

        $this->trace->info(TraceCode::PAYMENT_DOWNTIMES_TO_ACTIVATE, $downtimesToActivate->getIds());

        foreach ($downtimesToActivate as $downtime)
        {
            $downtime->setStatus(Status::STARTED);

            $this->getRepository()->saveOrFail($downtime);

            $this->eventDowntimeStarted($downtime);
        }

        return [
            'ids' => $downtimesToActivate->getIds(),
        ];
    }

    protected function resolveDowntimes()
    {
        $now = Carbon::now()->getTimestamp();

        $downtimesToResolve = $this->getRepository()->fetchPastScheduledDowntimesToResolve($now);

        $this->trace->info(TraceCode::PAYMENT_DOWNTIMES_TO_RESOLVE, $downtimesToResolve->getIds());

        foreach ($downtimesToResolve as $downtime)
        {
            $downtime->setStatus(Status::RESOLVED);

            $this->getRepository()->saveOrFail($downtime);

            $this->eventDowntimeResolved($downtime);
        }

        return [
            'ids' => $downtimesToResolve->getIds(),
        ];
    }

    public function eventDowntimeStarted(Entity $downtime)
    {
        $eventEnabledWebhooks = $this->repo
                                     ->webhook
                                     ->getWebhooksByEventEnabled(Event::PAYMENT_DOWNTIME_STARTED);

        foreach ($eventEnabledWebhooks as $webhook)
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN        => $downtime,
                ApiEventSubscriber::MERCHANT_ID => $webhook->merchant->getId(),
            ];

            $this->app['events']->fire('api.downtime.started', $eventPayload);
        }
    }

    public function eventDowntimeResolved(Entity $downtime)
    {
        $eventEnabledWebhooks = $this->repo
                                     ->webhook
                                     ->getWebhooksByEventEnabled(Event::PAYMENT_DOWNTIME_RESOLVED);

        foreach ($eventEnabledWebhooks as $webhook)
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN        => $downtime,
                ApiEventSubscriber::MERCHANT_ID => $webhook->merchant->getId(),
            ];

            $this->app['events']->fire('api.downtime.resolved', $eventPayload);
        }
    }
}
