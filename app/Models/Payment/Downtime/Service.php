<?php

namespace RZP\Models\Payment\Downtime;

use Mail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Mail\Downtime;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\Gateway\Downtime\Entity as GatewayEntity;

class Service extends Base\Service
{
    const RAZORX_DOWNTIME_V2 = "downtime_v2_webhook";

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
        $downtimes = $this->getRepository()->fetchOngoingDowntimes();

        return $downtimes->toArrayPublic();
    }

    public function triggerDowntimes(array $input, string $status): array
    {
        $this->increaseAllowedSystemLimits();

        $activateResponse = [];

        $resolveResponse =  [];

        if ($status === Status::STARTED)
        {
            $activateResponse = $this->activateDowntimes();
        }
        else if ($status === Status::RESOLVED)
        {
            $resolveResponse = $this->resolveDowntimes();
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY, null, null);
        }

        return [
            'activated' => $activateResponse,
            'resolved'  => $resolveResponse,
        ];
    }

    public function activateDowntimes()
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

    public function resolveDowntimes()
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

        $sendToOnlyTestMerchant = $this->sendDowntimeToMerchant($downtime, Status::STARTED);

        foreach ($eventEnabledWebhooks as $webhook)
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN        => $downtime,
                ApiEventSubscriber::MERCHANT_ID => $webhook->merchant->getId(),
            ];

            if($sendToOnlyTestMerchant === true)
            {
                $variant = $this->app->razorx->getTreatment($webhook->merchant->getId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')
                {
                    $this->app['events']->fire('api.payment.downtime.started', $eventPayload);
                }
            }
            else
            {
                $this->app['events']->fire('api.payment.downtime.started', $eventPayload);
            }
        }

        $this->emailDowntime(Constants::CREATED, $downtime);
    }

    public function eventDowntimeResolved(Entity $downtime)
    {
        $eventEnabledWebhooks = $this->repo
                                     ->webhook
                                     ->getWebhooksByEventEnabled(Event::PAYMENT_DOWNTIME_RESOLVED);

        $sendToOnlyTestMerchant = $this->sendDowntimeToMerchant($downtime, Status::RESOLVED);

        foreach ($eventEnabledWebhooks as $webhook)
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN        => $downtime,
                ApiEventSubscriber::MERCHANT_ID => $webhook->merchant->getId(),
            ];

            if($sendToOnlyTestMerchant === true)
            {
                $variant = $this->app->razorx->getTreatment($webhook->merchant->getId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')
                {
                    $this->app['events']->fire('api.payment.downtime.resolved', $eventPayload);
                }
            }
            else
            {
                $this->app['events']->fire('api.payment.downtime.resolved', $eventPayload);
            }
        }

        $this->emailDowntime(Constants::RESOLVED, $downtime);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    protected function sendDowntimeToMerchant($downtime, $status)
    {
        $downtimeArray = $downtime->toArray();

        $param =[
            GatewayEntity::METHOD => $downtimeArray[Entity::METHOD],
            GatewayEntity::BEGIN => $downtimeArray[Entity::BEGIN],
        ];

        if (isset($downtimeArray[Entity::ISSUER]) === true)
        {
            $param[GatewayEntity::ISSUER] = $downtimeArray[Entity::ISSUER];
        }
        if (isset($downtimeArray[Entity::NETWORK]) === true)
        {
            $param[GatewayEntity::NETWORK] = $downtimeArray[Entity::NETWORK];
        }

        if ($downtimeArray[Entity::METHOD] === Method::UPI)
        {
            // As Downtime V2 will be making upi downtimes for VPA Handles,
            // If VPA handle is not present we can assume that it was made by another source
            if (isset($downtimeArray[Entity::VPA_HANDLE]) === true)
            {
                $param[GatewayEntity::VPA_HANDLE] = $downtimeArray[Entity::VPA_HANDLE];
            }
            else
            {
                return false;
            }
        }

        $gatewayDowntime = null;

        if ($status === Status::STARTED)
        {
            $gatewayDowntime = $this->repo->gateway_downtime->fetchActiveDowntime($param);
        }
        else if ($status === Status::RESOLVED)
        {
            $gatewayDowntime = $this->repo->gateway_downtime->fetchResolvedDowntime($param);
        }

        $gatewayDowntime = $gatewayDowntime->unique(GatewayEntity::SOURCE);

        $source = $gatewayDowntime->pluck(GatewayEntity::SOURCE)->toArray();

        if (count($source) === 1 && (in_array(Source::DOWNTIME_V2, $source) === true))
        {
            return true;
        }

        return false;
    }

    public function emailDowntime(string $status, Entity $downtime)
    {
        $downtimeArray = $downtime->toArray();

        try
        {
            if ($status === Constants::CREATED)
            {
                $createEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::CREATED);

                Mail::send($createEmail);

                $this->trace->info(
                    TraceCode::PAYMENT_DOWNTIME_CREATE_EMAIL,
                    [
                        'message'            => 'Mail Sent'
                    ]);
            }
            elseif ($status === Constants::RESOLVED)
            {
                $resolveEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::RESOLVED);

                Mail::send($resolveEmail);

                $this->trace->info(
                    TraceCode::PAYMENT_DOWNTIME_RESOLVE_EMAIL,
                    [
                        'message'            => 'Mail Sent'
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_EMAIL_FAILED,
                [
                    'error'              => $e->getMessage(),
                    'status'             => $status,
                ]);
        }
    }
}
