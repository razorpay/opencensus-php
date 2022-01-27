<?php


namespace RZP\Services\Segment;


use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant;
use RZP\Jobs\SegmentRequestJob;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\AbstractEventClient;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org\Entity as OrgEntity;


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

    public function pushIdentifyEvent(Merchant\Entity $merchant, array $properties, int $eventTimestamp = null)
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

            if($eventTimestamp != null)
            {
                $eventData['timestamp'] = $eventTimestamp;
            }

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

    public function pushTrackEvent(
        Merchant\Entity $merchant, array $properties, string $eventName, int $eventTimestamp = null)
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

            if($eventTimestamp != null)
            {
                $eventData['timestamp'] = $eventTimestamp;
            }
            else
            {
                $eventData['timestamp'] = Carbon::now()->getTimestamp();
            }

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

    public function pushIdentifyAndTrackEvent(
        Merchant\Entity $merchant, array $properties, string $eventName, int $eventTimestamp = null)
    {
        if(empty($eventName) === false)
        {
            $properties['event_milestone'] = $eventName;
        }

        $this->pushIdentifyEvent($merchant, $properties, $eventTimestamp);
        $this->pushTrackEvent($merchant, $properties, $eventName, $eventTimestamp);
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
            Merchant\Entity::BUSINESS_BANKING => $merchant->isBusinessBankingEnabled(),
            "mcc"                           => $merchant->getCategory(),
            Constants::REGULAR_MERCHANT     => $this->isRegularMerchant($merchant)
        ];

        foreach (Constants::COMMON_MERCHANT_DETAIL_PROPERTIES as $attribute)
        {
            if($merchantDetail !== NULL) {
                $property = $merchantDetail->getAttribute($attribute);
                $properties[$attribute] = $property ?? 'NULL';
            }
        }

        return $properties;
    }

    protected function isRegularMerchant(Merchant\Entity $merchant) {
        if(empty($merchant->getParentId()) === false) {
            return false;
        }

        if(empty($merchant->getPartnerType()) === false) {
            return false;
        }

        if($merchant->getOrgId() != OrgEntity::RAZORPAY_ORG_ID) {
            return false;
        }

        $subMerchant = $this->repo->merchant_access_map->fetchSubMerchantOnMerchantId($merchant->getMerchantId());

        if(empty($subMerchant) === false) {
            return false;
        }

        return true;
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

    protected function getEventMilestone(array $eventData)
    {
        $eventType = $eventData['type'];

        if($eventType === 'track')
        {
            return $eventData['event'];
        }
        else
        {
            return $eventData['traits']['event_milestone'] ?? null;
        }
    }

    protected function verifyPropertiesAndPushMetrics(array $eventData)
    {
        try
        {
            $eventType = $eventData['type'];

            $eventProperties = [];
            if($eventType === 'identify')
            {
                $eventProperties = $eventData['traits'] ?? [];
            }
            else
            {
                $eventProperties = $eventData['properties'] ?? [];
            }

            $eventMilestone = $this->getEventMilestone($eventData);

            if(empty($eventMilestone) === true)
            {
                return;
            }

            $propertiesList = Config::MANDATORY_USER_PROPERTY_MAP[$eventMilestone] ?? null;

            if(empty($propertiesList) === false)
            {
                foreach ($propertiesList as $property)
                {
                    if(isset($eventProperties[$property]) === false)
                    {
                        $this->app['trace']->count(Metrics::SEGMENT_PROPERTY_MISSING_COUNT, [
                            'event_type'        => $eventType,
                            'event_milestone'   => $eventMilestone,
                            'property'          => $property
                        ]);
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SEGMENT_METRICS_PUSH_FAILURE, [
                'eventData' => $eventData
            ]);
        }
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

        $this->verifyPropertiesAndPushMetrics($eventData);

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

    public function buildRequestAndSend($batch = false)
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

                $this->sendEventRequest($headers, $url, $payload, $batch);
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

    protected function sendEventRequest(array $headers, string $url, array $eventData, $batch = false)
    {
        try
        {
            $request  = [
                'method'    => 'post',
                'url'       => $url,
                'headers'   => $headers,
                'content'   => json_encode($eventData),
                'options'   => [
                    'timeout'   => self::REQUEST_TIMEOUT
                ],
                'batch'     => $batch
            ];

            SegmentRequestJob::dispatch($request);
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
            ];

            $this->trace->error(TraceCode::EVENT_QUEUE_SEND_FAILED, $errorContext);
        }
    }
}
