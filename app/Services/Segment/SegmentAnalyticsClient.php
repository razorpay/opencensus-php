<?php


namespace RZP\Services\Segment;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\AbstractEventClient;
use RZP\Trace\TraceCode;

class SegmentAnalyticsClient extends AbstractEventClient
{
    protected $urlPattern;

    const TRACK_EVENT_URL_PATTERN = '/v1/batch';

    const SENSITIVE_KEYS = [
        'bank_account_number',
        'bank_branch_ifsc',
        'promoter_pan',
        'company_pan',
        'contact_email',
        'contact_mobile'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->config = $this->app['config']->get('services.segment_analytics');
    }

    public function pushIdentifyEvent(Merchant\Entity $merchant, array $properties)
    {
        if($this->shouldPushEvent($merchant) === false)
        {
            return;
        }

        try
        {
            $properties += $this->getMerchantProperties($merchant);

            $eventData = [
                'type'      => 'identify',
                'traits'    => $properties
            ];

            $this->pushEvent($merchant, $eventData);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'type'          => 'identify',
                'merchant_id'   => $merchant->getId()
            ]);
        }
    }

    public function pushTrackEvent(Merchant\Entity $merchant, array $properties, string $eventName)
    {
        if ($this->shouldPushEvent($merchant) === false)
        {
            return;
        }

        try
        {
            $properties += [
                Merchant\Entity::MERCHANT_ID    => $merchant->getId(),
                'event_category'                => Constants::SEGMENT_EVENT_CATEGORY,
                'event_action'                  => $eventName,
            ];

            $properties += $this->getUserProperties($merchant);

            $eventLabel = EventCode::EVENT_LABELS[$eventName] ?? "";

            if(empty($eventLabel) === false)
            {
                $properties['event_label'] = $eventLabel;
            }

            $eventData = [
                'type'                  => 'track',
                'properties'            => $properties,
                'event'                 => $eventName,
                Constants::INTEGRATIONS => $this->getIntegrations($merchant)
            ];

            $this->pushEvent($merchant, $eventData);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'type'          => 'track',
                'merchant_id'   => $merchant->getId()
            ]);
        }
    }

    public function pushIdentifyAndTrackEvent(Merchant\Entity $merchant, array $properties, string $eventName)
    {
        $this->pushIdentifyEvent($merchant, $properties);
        $this->pushTrackEvent($merchant, $properties, $eventName);
    }

    protected function shouldPushEvent(Merchant\Entity $merchant): bool
    {
        $isEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $merchant->getId(), RazorxTreatment::SEGMENT_ANALYTICS_FUNCTIONALITY);

        if($isEnabled === false)
        {
            return false;
        }

        return true;
    }

    protected function getMerchantProperties(Merchant\Entity $merchant)
    {
        $merchantDetail = $merchant->merchantDetail;

        $properties = [
            Merchant\Entity::PARENT_ID      => $merchant->getParentId(),
            Merchant\Entity::MERCHANT_ID    => $merchant->getId(),
            Merchant\Entity::PARTNER_TYPE   => $merchant->getPartnerType(),
            Merchant\Entity::ORG_ID         => $merchant->getOrgId(),
        ];

        foreach (Constants::COMMON_MERCHANT_DETAIL_PROPERTIES as $attribute)
        {
            $property = $merchantDetail->getAttribute($attribute);

            $properties[$attribute] = $property ?? 'NULL';
        }

        return $properties;
    }

    protected function getIntegrations(Merchant\Entity $merchant)
    {
        $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();

        if (empty($user) === true)
        {
            return [];
        }

        $appsflyerId = null;

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserId(
            $merchant->getId() ,$user->getId());

        if(empty($userDeviceDetail) === false)
        {
            $appsflyerId = $userDeviceDetail->getAppsFlyerId();
        }

        return [
            Constants::APPSFLYER     => [
                Constants::APPSFLYERID => $appsflyerId
            ]
        ];
    }

    protected function getUserProperties(Merchant\Entity $merchant)
    {
        $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();

        if (empty($user) === true)
        {
            return [];
        }

        $userId = $user->getId();

        $properties = [
            Constants::SOURCE           => 'BE',
            Constants::MODE             => $this->app['basicauth']->getMode() ?? "live",
            Constants::USER_ID          => $userId,
            Constants::USER_ROLE        => $this->app['basicauth']->getUserRole(),
        ];

        return $properties;
    }

    protected function pushEvent(Merchant\Entity $merchant, array $eventData)
    {
        $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();

        if (empty($user) === true)
        {
            return;
        }

        $userId = $user->getId();

        $eventData += [
            'userId'    => $userId,
        ];

        $this->maskSensitiveKeys($eventData);

        $this->trace->info(TraceCode::SEGMENT_EVENT_PUSH, [
            'eventData' => $eventData
        ]);

        $this->events[] = $eventData;
    }

    protected function maskSensitiveKeys(array & $properties)
    {
        foreach ($properties as $key => $value)
        {
            if(is_array($value))
            {
                $this->maskSensitiveKeys($properties[$key]);
            }
            else
            {
                if(in_array($key, self::SENSITIVE_KEYS, true))
                {
                    $properties[$key] = mask_except_last4($properties[$key]);
                }
            }
        }
    }

    public function buildRequestAndSend()
    {
        try {
            $eventData = $this->getEventTrackerData();

            if (empty($eventData) === true)
            {
                return false;
            }

            foreach ($eventData as $eventDataChunk)
            {
                $writeKey = $this->config['auth']['write_key'];

                $headers = [
                    'content-type'  => self::CONTENT_TYPE,
                    'Authorization'     => 'Basic '. base64_encode($writeKey . ':' . ''),
                ];

                $url = $this->config['url'] . $this->urlPattern;

                $payload = [
                    'batch' => $eventDataChunk['events']
                ];

                $this->sendEventRequest($headers, $url, $payload);
            }

            $this->flushEvents();
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
                'type'      => 'segment-analytics'
            ];

            $this->trace->error(TraceCode::EVENT_POST_FAILED, $errorContext);
        }
    }

}
