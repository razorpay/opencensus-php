<?php


namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class SignupWebAttributionAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $dataLakeCollector = $data["signup_web_attribution"]; // since data collector is an array

        $lakeData = $dataLakeCollector->getData();

        $this->pushEvent($lakeData, "identify");
        $this->pushEvent($lakeData, "track");

        return new ActionDto(Constants::SUCCESS);
    }

    private function pushEvent($lakeData, $eventType)
    {
        foreach ($lakeData as $data)
        {
            $merchantId = $data['id'];

            unset($data['id']);

            $segmentProperties = [];

            foreach ($data as $key => $value)
            {
                $segmentProperties["web_" . $key] = $value;
            }

            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $timestamp = $merchant->getCreatedAt();

            $segmentProperties["attribution_source"] = "Web";

            if($eventType === 'identify') {
                $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties, $timestamp);
            }
            else if($eventType === 'track') {
                $this->app['segment-analytics']->pushTrackEvent($merchant, $segmentProperties, SegmentEvent::SIGNUP_ATTRIBUTED, $timestamp);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);
    }
}
