<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class EmailNotVerifiedDataCollector extends TimeBoundDbDataCollector
{
    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $userIdList = $this->repo->user->filterEmailNotVerifiedUserIds($startTime, $endTime);

        $merchantIdList = array_unique($this->repo->merchant_user->fetchMerchantIdsForUserIdsAndRole($userIdList));

        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (24 * 60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - (24 * 60 * 60);
    }
}
