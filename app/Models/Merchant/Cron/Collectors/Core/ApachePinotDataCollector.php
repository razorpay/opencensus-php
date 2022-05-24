<?php


namespace RZP\Models\Merchant\Cron\Collectors\Core;


use RZP\Services\ApachePinotClient;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;

abstract class ApachePinotDataCollector extends BaseCollector
{
    abstract protected function getPinotQuery(): string;

    public function collect(): CollectorDto
    {
        $apachePinotService = new ApachePinotClient();

        $response = $apachePinotService->getDataFromPinot($this->getPinotQuery());

        return CollectorDto::create($response);
    }
}
