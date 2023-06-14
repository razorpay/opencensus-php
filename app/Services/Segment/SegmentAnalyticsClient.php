<?php


namespace RZP\Services\Segment;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Razorpay\Trace\Logger as Trace;
use Respect\Validation\Rules\Even;
use RZP\Constants\Timezone;
use RZP\Models\DeviceDetail\Constants as DeviceDetailConstants;
use RZP\Models\Merchant;
use RZP\Jobs\SegmentRequestJob;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\AbstractEventClient;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;

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
                $eventData['timestamp'] = Carbon::createFromTimestamp($eventTimestamp, Timezone::IST)->toISOString(true);
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
            $deviceDetails = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchant->getId());

            $gclid = $_COOKIE['gclid'] ?? Cookie::get('gclid');
            if (empty($gclid) == true and empty($deviceDetails) == false)
            {
                $gclid = $deviceDetails->getValueFromMetaData(DeviceDetailConstants::G_CLICK_ID);
            }

            $properties += [
                Merchant\Entity::MERCHANT_ID    => $merchant->getId(),
                'event_category'                => Constants::SEGMENT_EVENT_CATEGORY,
                'event_action'                  => $eventName,
                'gclid'                         => $gclid,
            ];

            if ($this->isFacebookPlatformEvent($eventName))
            {
                $properties += [
                    'action_source'     => Constants::ACTION_SOURCE
                ];

                if (empty($merchant->getEmail()) == false) {
                    $properties += [
                        'email'         => hash('sha256', $merchant->getEmail())
                    ];
                }

                if (empty(optional($merchant->merchantDetail)->getContactMobile()) == false) {
                    $properties += [
                        'phone'         => hash('sha256', $merchant->merchantDetail->getContactMobile())
                    ];
                }
            }

            $properties += $this->getUserProperties($merchant);

            $eventLabel = $this->getEventLabel($merchant,$eventName);

            if(empty($eventLabel) === false)
            {
                $properties['label'] = $eventLabel;
            }

            $eventData = [
                'type'                  => 'track',
                'properties'            => $properties,
                'event'                 => $eventName,
                Constants::INTEGRATIONS => $this->getIntegrations($merchant)
            ];

            if (empty($deviceDetails) == false and empty($deviceDetails->getSignupSource()) == false)
            {
                $eventData['context'] = [DeviceDetailConstants::DEVICE =>
                                             [DeviceDetailConstants::TYPE =>
                                                  $deviceDetails->getSignupSource()]];
            }

            if ($this->isFacebookPlatformEvent($eventName))
            {
                $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();
                if (empty($clientIpAddress) == true and empty($deviceDetails) == false)
                {
                    $clientIpAddress = $deviceDetails->getValueFromMetaData(DeviceDetailConstants::CLIENT_IP);
                }
                $eventData['context'][] = [DeviceDetailConstants::CLIENT_IP => $clientIpAddress];
            }

            if($eventTimestamp != null)
            {
                $eventData['timestamp'] = Carbon::createFromTimestamp($eventTimestamp, Timezone::IST)->toISOString(true);
            }
            else if($this->isEventXEvent($eventName) === false)
            {
                $eventData['timestamp'] = Carbon::now(Timezone::IST)->toISOString(true);
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

    public function getEventLabel(Merchant\Entity $merchant ,string $eventName)
    {
        $eventLabel = "";

        if( $this->isGoogleAnalyticsEvent($eventName) === true)
        {
            if($merchant->merchantDetail->isUnregisteredBusiness() === true)
            {
                $eventLabel .= MerchantDetail\BusinessType::UNREGISTERED;

                $merchantActivationFlow = $merchant->merchantDetail->getActivationFlow();

                if($merchantActivationFlow != null)
                {
                    $eventLabel .= "_".$merchantActivationFlow;
                }
            }
            else {

                $eventLabel .= MerchantDetail\BusinessType::REGISTERED;

                $merchantActivationFlow = $merchant->merchantDetail->getActivationFlow();

                if($merchantActivationFlow != null)
                {
                    $eventLabel .= "_".$merchantActivationFlow;
                }

            }
        }

        return $eventLabel;
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

        $isSubMerchant = (new AccessMapCore)->isSubMerchant($merchant->getMerchantId());

        return !$isSubMerchant;
    }

    protected function getIntegrations(Merchant\Entity $merchant)
    {
        $user = $this->app['basicauth']->getUser() ?? $merchant->users()->first();

        if (empty($user) === true)
        {
            return [];
        }

        $appsflyerId = null;
        $gaClientId = $_COOKIE['_ga'] ?? Cookie::get('_ga');
        if (empty($gaClientId) == false)
        {
            $gaClientId = substr($gaClientId,6);
        }

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserId(
            $merchant->getId() ,$user->getId());

        if(empty($userDeviceDetail) === false)
        {
            $appsflyerId = $userDeviceDetail->getAppsFlyerId();
            $gaClientId = $gaClientId ?? $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::G_CLIENT_ID);
        }

        return [
            Constants::APPSFLYER     => [
                Constants::APPSFLYERID => $appsflyerId
            ],
            Constants::GOOGLE_UNIVERSAL_ANALYTICS => [
                Constants::CLIENTID => $gaClientId
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
        try
        {
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

    protected function isEventXEvent(string $eventName){
        $xEventsName = [
            EventCode::USER_LOGIN,
            EventCode::FUND_ACCOUNT_ADDED,
            EventCode::CA_PAYOUT_PROCESSED,
            EventCode::VA_PAYOUT_PROCESSED,
            EventCode::CONTACT_CREATED,
            EventCode::CA_ACTIVATED,
            EventCode::X_SIGNUP_SUCCESS,
        ];

        $result = false;

        foreach($xEventsName as $event){
            if($event === $eventName){
                $result = true;
            }
        }

        return $result;
    }

    protected function isGoogleAnalyticsEvent(string $eventName){

        $googleAnalyticsEvents = [
            EventCode::MTU_TRANSACTED,
            EventCode::PAYMENTS_ENABLED,
            EventCode::L1_SUBMISSION,
            EventCode::L2_SUBMISSION
        ];

        if(in_array($eventName, $googleAnalyticsEvents) === true)
        {
            return true;
        }

        return false;
    }

    protected function isFacebookPlatformEvent(string $eventName){

        $facebookPlatformEvents = [
            EventCode::L1_SUBMISSION,
            EventCode::L2_SUBMISSION,
            EventCode::MTU_TRANSACTED,
            EventCode::LEAD_SCORE_CALCULATED
        ];

        if(in_array($eventName, $facebookPlatformEvents) === true)
        {
            return true;
        }

        return false;
    }
}
