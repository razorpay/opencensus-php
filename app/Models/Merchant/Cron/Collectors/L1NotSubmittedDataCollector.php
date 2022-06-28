<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\DeviceDetail\Constants;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class L1NotSubmittedDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $merchantIdList = $this->repo->merchant_detail->filterL1NotSubmittedMerchantIds($startTime, $endTime);

        $merchantIdList = $this->repo->user_device_detail->removeSignupCampaignIdsFromMerchantIdList($merchantIdList, Constants::EASY_ONBOARDING);

        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        // TODO: Implement getStartInterval() method.
    }

    protected function getEndInterval(): int
    {
        // TODO: Implement getEndInterval() method.
    }
}
