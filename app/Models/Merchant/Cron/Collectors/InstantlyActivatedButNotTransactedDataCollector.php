<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\DeviceDetail\Constants;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class InstantlyActivatedButNotTransactedDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $merchantIdList = $this->repo->merchant->fetchAllInstantlyActivatedMerchants($startTime, $endTime);

        $merchantIdList = $this->repo->user_device_detail->removeSignupCampaignIdsFromMerchantIdList($merchantIdList, Constants::EASY_ONBOARDING);

        $transactedMerchants = $this->repo->transaction->filterMerchantsWithFirstTransactionAboveTimestamp(
            $merchantIdList, $startTime);

        $merchantList =  array_diff($merchantIdList, $transactedMerchants);

        $data["merchantIds"] = $merchantList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (1 * 60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (1 * 60 * 60);
    }
}
