<?php

namespace RZP\Models\Payment\Downtime;

use Mail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Mail\Downtime;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Webhook\Event;

class Service extends Base\Service
{
    const RAZORX_DOWNTIME_V2 = "downtime_v2_webhook";

    const DOWNTIME_WEBHOOK_TIMEOUT = 15000;

    protected function getRepository()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }

    public function createFromGatewayDowntimes(array $input)
    {
        return $this->core()->createFromGatewayDowntimes($input);
    }

    public function refreshOngoingDowntimesCache()
    {
        $this->core()->refreshOngoingDowntimesCache([]);
    }

    public function refreshHistoricalDowntimeCache($lookbackPeriod=0)
    {
        $this->core()->refreshHistoricalDowntimeCache($lookbackPeriod);
    }

    public function refreshScheduledDowntimesCache()
    {
        $this->core()->refreshScheduledDowntimesCache();
    }

    public function getMethodDowntimeDataForMerchant(array $input): array
    {
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getMerchantId(),
            Merchant\RazorxTreatment::SEND_MERCHANT_DOWNTIMES,
            $this->mode
        );

        $this->trace->info(
            TraceCode::PAYMENT_DOWNTIMES_MERCHANT_ID,
            [
                'merchantId' => $this->merchant->getMerchantId(),
                'variant' => $variant
            ]
        );

        if (strtolower($variant) === 'on')
        {
            $downtimes = $this->getRepository()->fetchOngoingPlatformAndMerchantDowntimes($this->merchant->getMerchantId());
        }
        else
        {
            $downtimes = $this->getRepository()->fetchOngoingDowntimes();
        }

        return $downtimes->toArrayPublic();
    }

    public function fetchOngoingDowntimes(): array
    {
        $this->trace->info(TraceCode::FETCH_ONGOING_PLATFORM_LEVEL_DOWNTIMES, ["merchantId" => $this->merchant->getId()]);

        return $this->core()->fetchOngoingDowntimes();
    }

    public function fetchResolvedDowntimes($params): array
    {
        $this->trace->info(TraceCode::FETCH_RESOLVED_PLATFORM_LEVEL_DOWNTIMES,
                           ["merchantId" => $this->merchant->getId(), "filters" => $params]);
        $this->validateRequestParams($params);

        return $this->core()->fetchResolvedDowntimes($params);
    }

    public function fetchScheduledDowntimes()
    {
        $this->trace->info(TraceCode::FETCH_PLATFORM_SCHEDULED_DOWNTIMES,  ["merchantId" => $this->merchant->getId()]);
        return $this->core()->fetchScheduledDowntimes();
    }

    public function getPaymentDowntimeByID(array $input, string $id): array
    {
        $id = str_replace("down_", "", $id);

        $downtimes = $this->getRepository()->findOrFailPublic($id);

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

    public function eventDowntimeStarted(Entity $downtime, $lastSeverity=null)
    {
        try
        {
            $merchantIds = [];
            // @see getMerchantsSubscribingToWebhookEvent method.
            if($downtime->getMerchantId() === null)
            {
                $merchantIds = $this->getMerchantsSubscribingToWebhookEvent(Event::PAYMENT_DOWNTIME_STARTED);
            }
            else
            {
                $variant = $this->app->razorx->getTreatment($downtime->getMerchantId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')
                {
                    $merchantIds = $this->getMerchantsSubscribingToWebhookEventForMerchant(Event::PAYMENT_DOWNTIME_STARTED, $downtime->getMerchantId());
                }
            }

            foreach ($merchantIds as $merchantId)
            {
                $eventPayload = [
                    ApiEventSubscriber::MAIN        => $downtime,
                    ApiEventSubscriber::MERCHANT_ID => $merchantId,
                ];

                $this->app['events']->dispatch('api.payment.downtime.started', $eventPayload);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_CREATE_WEBHOOK_FAILED
            );
        }
    }

    public function eventDowntimeResolved(Entity $downtime, $lastSeverity=null)
    {
        try {
            $merchantIds = [];
            // @see getMerchantsSubscribingToWebhookEvent method.
            if($downtime->getMerchantId() === null)
            {
                $merchantIds = $this->getMerchantsSubscribingToWebhookEvent(Event::PAYMENT_DOWNTIME_RESOLVED);
            }
            else
            {
                $variant = $this->app->razorx->getTreatment($downtime->getMerchantId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')

                {
                    $merchantIds = $this->getMerchantsSubscribingToWebhookEventForMerchant(Event::PAYMENT_DOWNTIME_RESOLVED, $downtime->getMerchantId());
                }
            }

            foreach ($merchantIds as $merchantId)
            {
                $eventPayload = [
                    ApiEventSubscriber::MAIN        => $downtime,
                    ApiEventSubscriber::MERCHANT_ID => $merchantId,
                ];

                $this->app['events']->dispatch('api.payment.downtime.resolved', $eventPayload);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_RESOLVE_WEBHOOK_FAILED
            );
        }
    }

    /**
     * Stork does not have multi-casting support yet. It expects webhook event
     * on behalf of a merchant/owner.
     *
     * This function returns maximum of 5k merchant subscribing to given event.
     * The callee then processes general events like payment.downtime.started
     * on behalf of each merchant, one by one.
     *
     * Gets merchant ids subscribing to given webhook event.
     * @param  string $event
     * @return array An array of merchant ids.
     * @throws \RZP\Exception\ServerErrorException
     */
    protected function getMerchantsSubscribingToWebhookEvent(string $event): array
    {
        /** @var \RZP\Services\Stork */
        $service = $this->app->stork_service;

        $service->init($this->mode);

        // Attempts twice before throwing exception.
        $response = $service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            [
                'service'    => $service->service,
                'owner_type' => 'merchant',
                'limit'      => 5000,
                'active'     => true,
                'event'      => $event,
            ],
            self::DOWNTIME_WEBHOOK_TIMEOUT
        );

        $body = json_decode($response->body, true) ?: [];
        $webhooks = $body['webhooks'] ?? [];

        return  array_values(array_unique(array_pluck($webhooks, 'owner_id')));
    }

    protected function getMerchantsSubscribingToWebhookEventForMerchant(string $event, string $mid): array
    {
        /** @var \RZP\Services\Stork */
        $service = $this->app->stork_service;

        $service->init($this->mode);

        // Attempts twice before throwing exception.
        $response = $service->request(
            '/twirp/rzp.stork.webhook.v1.WebhookAPI/List',
            [
                'service'    => $service->service,
                'owner_type' => 'merchant',
                'limit'      => 5000,
                'active'     => true,
                'event'      => $event,
                'owner_id'   => $mid,
            ]
        );

        $body = json_decode($response->body, true) ?: [];
        $webhooks = $body['webhooks'] ?? [];

        return  array_values(array_unique(array_pluck($webhooks, 'owner_id')));
    }


    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    public function emailDowntime(string $status, Entity $downtime, $lastSeverity=null)
    {

        if($downtime->getMerchantId() !== null)
        {
            $this->trace->info(TraceCode::SKIP_MERCHANT_DOWNTIME_COMMUNICATION, ['merchantId' => $downtime->getMerchantId()]);
            return;
        }

        $downtimeArray = $downtime->toArray();

        $recipientEmail = null;

        if($downtime->getMerchantId() !== null)
        {
            $variant = $this->app->razorx->getTreatment($downtime->getMerchantId(), self::RAZORX_DOWNTIME_V2, $this->mode);

            if (strtolower($variant) === 'on')
            {
                $email = $this->repo->merchant->fetchAllMerchantContacts([$downtime->getMerchantId()])->get()->toArray();
                $recipientEmails = array_pop($email);
                $recipientEmail = [$recipientEmails['email']];
            }
            else
            {
                $recipientEmail = [
                    'product.onlinepayments@razorpay.com',
                    'tech.onlinepayments.routing@razorpay.com',
                    'srm@razorpay.com'
                ];
            }
        }

        try
        {
            if ($status === Constants::CREATED)
            {
                $createEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::CREATED, $recipientEmail, $lastSeverity);

                Mail::send($createEmail);

                $this->trace->info(
                    TraceCode::PAYMENT_DOWNTIME_CREATE_EMAIL,
                    [
                        'message'            => 'Mail Sent',
                        'id'                 => $downtimeArray['id'],
                    ]);
            }
            elseif ($status === Constants::RESOLVED)
            {
                $resolveEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::RESOLVED, $recipientEmail);

                Mail::send($resolveEmail);

                $this->trace->info(
                    TraceCode::PAYMENT_DOWNTIME_RESOLVE_EMAIL,
                    [
                        'message'            => 'Mail Sent',
                        'id'                 => $downtimeArray['id'],
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
                    'id'                 => $downtimeArray['id'],
                ]);
        }
    }

    private function validateRequestParams($params)
    {
        if (isset($params['startDate']) === false || isset($params['endDate'])===false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
                null, null, "startDate and endDate should be provided");
        }
        $sdEpoc = strtotime($params['startDate'].' Asia/Kolkata');
        $edEpoc = strtotime($params['endDate'].' Asia/Kolkata');

        $tDiff = ($edEpoc - $sdEpoc)/86400;
        if($tDiff < 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
                null, null, "startDate should never be greater than endDate");
        }

        if($tDiff > Constants::MAX_LOOKBACK_PERIOD)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
                null, null, "Date range should be within 30 days");
        }
    }
}
