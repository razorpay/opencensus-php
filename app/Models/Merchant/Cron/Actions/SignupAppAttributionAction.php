<?php


namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\DeviceDetail\Attribution;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class SignupAppAttributionAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $appAttributionCollector = $data["signup_app_attribution"];

        $appAttributionData = $appAttributionCollector->getData();

        $this->pushEvent($appAttributionData, "identify");
        $this->pushEvent($appAttributionData, "track");

        return new ActionDto(Constants::SUCCESS);
    }

    private function pushEvent($appAttributionData, $eventType){
        foreach ($appAttributionData as $data)
        {
            $merchantId = $data['merchant_id'];

            unset($data['merchant_id']);

            $segmentProperties = [];

            $campaignAttributes = $data[Attribution\Entity::CAMPAIGN_ATTRIBUTES] ?? [];

            if(empty($campaignAttributes) === false) {
                foreach ($campaignAttributes as $key => $value) {
                    $data[$key] = $value;
                }

                unset($data[Attribution\Entity::CAMPAIGN_ATTRIBUTES]);
            }

            unset($data[Attribution\Entity::CREATED_AT]);

            foreach ($data as $key => $value)
            {
                $segmentProperties["app_" . $key] = $value;
            }

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $segmentProperties["attribution_source"] = "App";

            $timestamp = $merchant->getCreatedAt();

            if($eventType === 'identify')
            {
                $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties, $timestamp);
            }
            else if($eventType === 'track')
            {
                $this->app['segment-analytics']->pushTrackEvent($merchant, $segmentProperties, SegmentEvent::SIGNUP_ATTRIBUTED, $timestamp);
            }
        }


        $this->app['segment-analytics']->buildRequestAndSend(true);
    }
}
