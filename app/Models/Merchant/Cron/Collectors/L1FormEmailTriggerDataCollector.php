<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class L1FormEmailTriggerDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'       => $this->args,
            'start_time' => $startTime,
            'end_time'   => $endTime
        ]);

        $merchantIdList = $this->repo->merchant_detail->filterL1NotSubmittedMerchantIdsWithEmailId($startTime, $endTime);

        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (60 * 60);
    }
}
