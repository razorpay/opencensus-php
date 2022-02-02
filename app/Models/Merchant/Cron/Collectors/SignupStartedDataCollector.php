<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class SignupStartedDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $merchantIdList = $this->repo->merchant->fetchMerchantsCreatedBetween($startTime, $endTime);

        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (15 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (15 * 60);
    }
}
