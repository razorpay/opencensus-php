<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\SumoLogsDataCollector;

class MtuTransactedSumoLogsCollector extends SumoLogsDataCollector
{
    protected function getSumoQuery(): string
    {
        return "SEGMENT_EVENT_PUSH | json auto | where %context.eventData.type=\"track\" and %context.eventData.event= \"MTU Transacted\"";
    }

    protected function getStartInterval(): int
    {
        return Carbon::today(Timezone::IST)->startOfDay()->getTimestamp();
    }

    protected function getEndInterval(): int
    {
        return Carbon::now(Timezone::IST)->getTimestamp();
    }
}
