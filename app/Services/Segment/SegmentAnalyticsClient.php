<?php


namespace RZP\Services\Segment;


use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\AbstractEventClient;
use RZP\Trace\TraceCode;

class SegmentAnalyticsClient extends AbstractEventClient
{
    protected $urlPattern;

    const TRACK_EVENT_URL_PATTERN = '/v1/batch';

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
        
        $properties += $this->getMerchantProperties($merchant);

        $eventData = [
            'type'      => 'identify',
            'traits'    => $properties
        ];

        $this->pushEvent($merchant, $eventData);
    }

    public function pushTrackEvent(Merchant\Entity $merchant, array $properties, string $eventName)
    {
        if ($this->shouldPushEvent($merchant) === false)
        {
            return;
        }

        $properties += [
            'merchant_id'   => $merchant->getId()
        ];

        $eventData = [
            'type'          => 'track',
            'properties'    => $properties,
            'event'         => $eventName
        ];

        $this->pushEvent($merchant, $eventData);
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
            Merchant\Entity::MERCHANT_ID    => $merchant->getId(),
            Merchant\Entity::PARTNER_TYPE   => $merchant->getPartnerType(),
        ];

        foreach (Constants::COMMON_MERCHANT_DETAIL_PROPERTIES as $attribute)
        {
            $properties[$attribute] = $merchantDetail->getAttribute($attribute);
        }

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

        $this->events[] = $eventData;
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
