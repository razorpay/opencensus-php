<?php


namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Services\Segment;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class WebAttributionAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collector = $data["web_attribution"];

        $lakeData =  $collector->getData();

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

            $segmentProperties[Segment\Constants::EVENT_MILESTONE] = SegmentEvent::IDENTIFY_WEB_ATTRIBUTION;

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
        }

        $this->app['segment-analytics']->buildRequestAndSend(true);

        return new ActionDto(Constants::SUCCESS);
    }
}
