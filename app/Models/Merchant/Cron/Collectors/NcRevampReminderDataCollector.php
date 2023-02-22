<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;

class NcRevampReminderDataCollector extends TimeBoundDbDataCollector
{
    // where day is the number of days after which we will send reminder to the
    // merchant

    protected $day = 1;

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        // According to current implementation the value of X is kept as 1 day

         //merchants that were moved to NC between start and end time
        $merchantIdList = $this->repo->state->getEntityIdsWithNameInRange(
            DetailStatus::NEEDS_CLARIFICATION,
            $startTime,
            $endTime
        );

        ////filter merchants who are currently in NC
        $merchantIdList = $this->repo->merchant_detail->filterMerchantIdsByActivationStatus(
            $merchantIdList, [
            DetailStatus::NEEDS_CLARIFICATION
        ]);


        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - ($this->day * 24 * 60 * 60);
    }

    protected function getEndInterval(): int
    {
        return $this->cronStartTime - ($this->day * 24 * 60 * 60);
    }
}
