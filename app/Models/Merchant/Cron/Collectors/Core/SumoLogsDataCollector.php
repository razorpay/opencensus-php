<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;


use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Services\SumoLogic\Service as SumoService;

abstract class SumoLogsDataCollector extends TimeBoundDbDataCollector
{
    abstract protected function getSumoQuery(): string;

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $sumoService = new SumoService();

        $messageCount = $sumoService->searchCount($this->getSumoQuery(), $startTime, $endTime);

        return CollectorDto::create($messageCount);
    }
}
