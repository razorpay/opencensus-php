<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;


use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

abstract class TimeBoundDbDataCollector extends DbDataCollector
{
    abstract protected function collectDataWithinInterval($startTime, $endTime): CollectorDto;

    abstract protected function getStartInterval(): int;

    abstract protected function getEndInterval(): int;

    /**
     * Sometimes we need to run the cron job manually for a specific time window.
     * This method will return start_time if its present in args;
     * @return int|null
     */
    private function getStartIntervalFromArgs(): ?int
    {
        return $this->args['start_time'] ?? null;
    }

    /**
     * Sometimes we need to run the cron job manually for a specific time window.
     * This method will return end_time if its present in args;
     * @return int|null
     */
    private function getEndIntervalFromArgs(): ?int
    {
        return $this->args['end_time'] ?? null;
    }

    protected function collectDataFromSource(): CollectorDto
    {
        $startTime = $this->getStartIntervalFromArgs() ?? $this->getStartInterval();
        $endTime = $this->getEndIntervalFromArgs() ?? $this->getEndInterval();

        $this->app['trace']->info(TraceCode::DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        return $this->collectDataWithinInterval($startTime, $endTime);
    }
}
