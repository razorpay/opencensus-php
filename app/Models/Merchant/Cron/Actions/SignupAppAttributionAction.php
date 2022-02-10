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

        foreach ($appAttributionData as $data)
        {
            $merchantId = $data['merchant_id'];

            unset($data['merchant_id']);

            $segmentProperties = [];

            $timestamp = null;

            $campaignAttributes = $data[Attribution\Entity::CAMPAIGN_ATTRIBUTES] ?? [];

            if(empty($campaignAttributes) === false) {
                foreach ($campaignAttributes as $key => $value) {
                    $data[$key] = $value;
                }

                unset($data[Attribution\Entity::CAMPAIGN_ATTRIBUTES]);
            }

            $timestamp = $data[Attribution\Entity::CREATED_AT];
            unset($data[Attribution\Entity::CREATED_AT]);

            foreach ($data as $key => $value)
            {
                $segmentProperties["app_" . $key] = $value;
            }

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant, $segmentProperties, SegmentEvent::SIGNUP_ATTRIBUTED, $timestamp);
        }


        $this->app['segment-analytics']->buildRequestAndSend();

        return new ActionDto(Constants::SUCCESS);
    }
}
