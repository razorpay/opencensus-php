<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Services\Segment\EventCode as SegmentEvent;

class AppsflyerUninstallAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data['appsflyer_uninstall_events']; // since data collector is an array

        $appsflyerIdList = $collectorData->getData();

        if (count($appsflyerIdList) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;

        foreach ($appsflyerIdList as $appsflyerId => $eventTime)
        {
            try
            {
                $this->pushSegmentEvent($appsflyerId, $eventTime);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'args'        => $this->args,
                    'appsflyerId' => $appsflyerId
                ]);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend();

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($appsflyerIdList)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function pushSegmentEvent($appsflyerId, $eventTime)
    {
        $eventTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $eventTime, Timezone::IST)->getTimestamp();

        $userDeviceDetails = $this->repo->user_device_detail->fetchByAppsflyerId($appsflyerId);

        if (empty($userDeviceDetails) === true)
        {
            $this->app['trace']->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'  => $appsflyerId,
                'error' => 'missing user device details'
            ]);

            return;
        }

        $merchantId = $userDeviceDetails->getMerchantId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant, [], SegmentEvent::APPSFLYER_UNINSTALL, $eventTimestamp);
    }
}
