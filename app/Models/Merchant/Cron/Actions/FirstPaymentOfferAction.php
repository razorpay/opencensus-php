<?php

namespace RZP\Models\Merchant\Cron\Actions;

use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Escalations\Core as EscalationCore;
use RZP\Services\Segment\EventCode as SegmentEvent;

class FirstPaymentOfferAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        $collectorData = $data["merchant_notification_data"]; // since data collector is an array

        $data = $collectorData->getData();

        $merchantIds = $data["merchantIds"];

        if (count($merchantIds) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = (new EscalationCore())->sendNotificationUtility(
            $merchantIds,
            $this->args['event_name']
        );

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIds)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        $merchants = $this->repo->merchant->getMerchantsFromMerchantIdList($merchantIds);

        foreach ($merchants as $merchant) {
            $this->pushSegmentEvent($merchant);
            $this->startHubspotEvent($merchant);
        }
        return new ActionDto($status);
    }

    private function pushSegmentEvent($merchant)
    {
        $properties = [];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::OFFERMTU_TARGETED_MERCHANT);
    }

    private function startHubspotEvent($merchant)
    {
        if ($merchant->getEmail() !== null)
        {
            $this->app->hubspot->trackHubspotEvent($merchant->getEmail(), [
                'Offermtu Targeted Merchant' => true
            ]);
        }
    }
}
