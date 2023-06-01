<?php

namespace RZP\Models\Payment\Downtime;

use Mail;

use Carbon\Carbon;
use Redis;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Mail\Downtime;
use RZP\Models\Gateway\Downtime\Webhook\Constants\DowntimeService;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Feature;
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
        $sendMerchantDowntimesInFetchApi = $this->shouldSendMerchantDowntimes(Constants::FETCH_API);

        if ($sendMerchantDowntimesInFetchApi &&
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GRANULAR_DOWNTIMES) === true))
        {
            $downtimes = $this->getRepository()->fetchOngoingPlatformAndMerchantDowntimes($this->merchant->getMerchantId());

            return $downtimes->toArrayPublic();
        }
        else
        {
            $downtimes = $this->getRepository()->fetchOngoingDowntimes();

            $downtimesArrayPublic = $downtimes->toArrayPublic();

            $this->removeGranularDowntimeKeysFromCollection($downtimesArrayPublic);

            return $downtimesArrayPublic;
        }
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

        $sendMerchantDowntimesInFetchApi = $this->shouldSendMerchantDowntimes(Constants::FETCH_API);

        if ($sendMerchantDowntimesInFetchApi &&
            ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GRANULAR_DOWNTIMES) === true))
        {
            return $downtimes->toArrayPublic();
        }
        else
        {
            $downtimeArrayPublic = $downtimes->toArrayPublic();

            $this->removeGranularDowntimeKeysFromEntity($downtimeArrayPublic);

            return $downtimeArrayPublic;
        }
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
        $downtimeType = ($downtime->getMerchantId() === null) ? DowntimeService::PLATFORM : DowntimeService::MERCHANT;

        try
        {
            $merchantIds = [];
            // @see getMerchantsSubscribingToWebhookEvent method.
            if($downtime->getMerchantId() === null)
            {
                $merchantIds = array_merge(
                    $this->getMerchantsSubscribingToWebhookEvent(Event::PAYMENT_DOWNTIME_STARTED),
                    $this->getAdditionalMerchantsSubscribingToWebhook()
                );
            }
            else
            {
                $variant = $this->app->razorx->getTreatment($downtime->getMerchantId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')
                {
                    $merchantIds = $this->getMerchantsSubscribingToWebhookEventForMerchant(Event::PAYMENT_DOWNTIME_STARTED, $downtime->getMerchantId());
                }
            }

            $sendMerchantDowntimesInWebhooks = $this->shouldSendMerchantDowntimes(Constants::WEBHOOKS);

            foreach ($merchantIds as $merchantId)
            {
                $this->trace->count(Metric::DOWNTIME_WEBHOOK_ATTEMPTED_COUNT, ['downtime_type' => $downtimeType, 'status' => Status::STARTED]);

                $eventPayload = [
                    ApiEventSubscriber::MAIN        => $downtime,
                    ApiEventSubscriber::MERCHANT_ID => $merchantId,
                    ApiEventSubscriber::WITH        => $sendMerchantDowntimesInWebhooks
                ];

                try
                {
                    $this->app['events']->dispatch('api.payment.downtime.started', $eventPayload);
                }
                catch (\Exception $e)
                {
                    $this->trace->info(TraceCode::PAYMENT_DOWNTIME_CREATE_WEBHOOK_FAILED, ["merchant_id" => $merchantId, "exception" => $e]);

                    $this->trace->count(Metric::DOWNTIME_WEBHOOK_FAILED_COUNT, ['downtime_type' => $downtimeType, 'status' => Status::STARTED]);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_CREATE_WEBHOOK_JOB_FAILED
            );
        }
    }

    public function eventDowntimeUpdated(Entity $downtime, $lastSeverity=null)
    {
        try
        {
            $merchantIds = [];

            if($downtime->getMerchantId() === null)
            {
                $merchantIds = array_merge(
                    $this->getMerchantsSubscribingToWebhookEvent(Event::PAYMENT_DOWNTIME_UPDATED),
                    $this->getAdditionalMerchantsSubscribingToWebhook()
                );
            }
            else
            {
                $merchantIds = $this->getMerchantsSubscribingToWebhookEventForMerchant(Event::PAYMENT_DOWNTIME_UPDATED, $downtime->getMerchantId());
            }

            $sendMerchantDowntimesInWebhooks = true;

            foreach ($merchantIds as $merchantId)
            {
                $eventPayload = [
                    ApiEventSubscriber::MAIN        => $downtime,
                    ApiEventSubscriber::MERCHANT_ID => $merchantId,
                    ApiEventSubscriber::WITH        => $sendMerchantDowntimesInWebhooks
                ];

                $this->app['events']->dispatch('api.payment.downtime.updated', $eventPayload);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_UPDATED_WEBHOOK_FAILED
            );
        }
    }

    public function eventDowntimeResolved(Entity $downtime, $lastSeverity=null)
    {
        $downtimeType = ($downtime->getMerchantId() === null) ? DowntimeService::PLATFORM : DowntimeService::MERCHANT;

        try {
            $merchantIds = [];
            // @see getMerchantsSubscribingToWebhookEvent method.
            if($downtime->getMerchantId() === null)
            {
                $merchantIds = array_merge(
                    $this->getMerchantsSubscribingToWebhookEvent(Event::PAYMENT_DOWNTIME_RESOLVED),
                    $this->getAdditionalMerchantsSubscribingToWebhook()
                );
            }
            else
            {
                $variant = $this->app->razorx->getTreatment($downtime->getMerchantId(), self::RAZORX_DOWNTIME_V2, $this->mode);

                if (strtolower($variant) === 'on')

                {
                    $merchantIds = $this->getMerchantsSubscribingToWebhookEventForMerchant(Event::PAYMENT_DOWNTIME_RESOLVED, $downtime->getMerchantId());
                }
            }

            $sendMerchantDowntimesInWebhooks = $this->shouldSendMerchantDowntimes(Constants::WEBHOOKS);

            foreach ($merchantIds as $merchantId)
            {
                $this->trace->count(Metric::DOWNTIME_WEBHOOK_ATTEMPTED_COUNT, ['downtime_type' => $downtimeType, 'status' => Status::RESOLVED]);

                $eventPayload = [
                    ApiEventSubscriber::MAIN        => $downtime,
                    ApiEventSubscriber::MERCHANT_ID => $merchantId,
                    ApiEventSubscriber::WITH        => $sendMerchantDowntimesInWebhooks
                ];

                try
                {
                    $this->app['events']->dispatch('api.payment.downtime.resolved', $eventPayload);
                }
                catch (\Exception $e)
                {
                    $this->trace->info(TraceCode::PAYMENT_DOWNTIME_RESOLVE_WEBHOOK_FAILED, ["merchant_id" => $merchantId, "exception" => $e]);

                    $this->trace->count(Metric::DOWNTIME_WEBHOOK_FAILED_COUNT, ['downtime_type' => $downtimeType, 'status' => Status::RESOLVED]);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_DOWNTIME_RESOLVE_WEBHOOK_JOB_FAILED
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

    protected function getAdditionalMerchantsSubscribingToWebhook(): array
    {
        return array("HscZ2md6SOPF3U", "8RerE9oY0d7rbC", "IDqz9K5dictPEu");
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(300);
    }

    public function emailDowntime(string $status, Entity $downtime, $lastSeverity=null)
    {
        $merchantDowntimesEnabled = false;

        $cc = null;

        if($downtime->getMerchantId() !== null)
        {
            $this->merchant = $this->repo->merchant->findOrFail($downtime->getMerchantId());

            if (($this->shouldSendMerchantDowntimes(Constants::EMAILS) === true)
                && ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_GRANULAR_DOWNTIMES) === true))
            {
                $merchantDowntimesEnabled = true;
            }
            else
            {
                $this->trace->info(TraceCode::SKIP_MERCHANT_DOWNTIME_COMMUNICATION, ['merchantId' => $downtime->getMerchantId()]);

                return;
            }
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
                $this->trace->info(TraceCode::SKIP_MERCHANT_DOWNTIME_COMMUNICATION, ['merchantId' => $downtime->getMerchantId()]);

                return;
            }

            $cc = $this->getValueFromRedis(Constants::DOWNTIMES_EMAIL_CC . $downtime->getMerchantId());

            if (isset($cc) === false)
            {
                $cc = 'keyaccounts@razorpay.com';
            }

            $ids = array('8RerE9oY0d7rbC', 'GCwhxngAcMtWC8', 'GDJYY4pJqT0cQ5', '5Q9dttFwD5E89W', 'GtFwVSbNTDTM9C', 'GtG3WLjGVjzx2n');

            if(in_array($downtime->getMerchantId(), $ids))
            {
                $recipientEmail = array('ameya@ixigo.com','noc@travenues.com');
            }
        }

        try
        {
            if ($status === Constants::CREATED)
            {
                $createEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::CREATED, $merchantDowntimesEnabled, $recipientEmail, $cc, $lastSeverity);

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
                $resolveEmail = new Downtime\DowntimeNotification($downtimeArray, Constants::RESOLVED, $merchantDowntimesEnabled, $recipientEmail, $cc);

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


    public function shouldSendMerchantDowntimes(string $communicationChannel):bool
    {
        $isExperimentActivated = strtolower(
                $this->app->razorx->getTreatment(
                    $communicationChannel,
                    Merchant\RazorxTreatment::SEND_MERCHANT_DOWNTIMES,
                    $this->mode
                )) === 'on';

        return $isExperimentActivated;
    }

    public function removeGranularDowntimeKeysFromCollection(array & $downtimesArrayPublic)
    {
        if(isset($downtimesArrayPublic["items"]) === true) {
            foreach ($downtimesArrayPublic["items"] as $key => $downtime) {
                $this->removeGranularDowntimeKeysFromEntity($downtimesArrayPublic["items"][$key]);
            }
        }
    }

    public function removeGranularDowntimeKeysFromEntity(array & $downtimeArrayPublic)
    {
        unset($downtimeArrayPublic[Entity::INSTRUMENT_SCHEMA]);
        unset($downtimeArrayPublic[Entity::INSTRUMENT][Entity::TYPE]);
        unset($downtimeArrayPublic[Entity::INSTRUMENT][Entity::FLOW]);

        if (($downtimeArrayPublic[Entity::STATUS] === Status::UPDATED))
        {
            $downtimeArrayPublic[Entity::STATUS] = Status::STARTED;
        }
    }

    public function getValueFromRedis(string $key)
    {
        try
        {
            $redis = Redis::connection();

            $value = $redis->get($key);

            if ($value !== false) {
                return $value;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::DOWNTIME_FETCH_CC_FROM_REDIS_FAILURE, ['Key' => $key,]);
        }
    }

}
