<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Database\Connection;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Traits\ConnectionFallbackMechanism;
use RZP\Trace\TraceCode;

class MtuTransactedDataCollector extends TimeBoundDbDataCollector
{
    use ConnectionFallbackMechanism;

    protected $name = "mtu_transacted_merchants";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        // Filter out all merchants that have transacted since last time cron ran
        $transactedMerchants = $this->getRepository("transaction")->fetchTransactedMerchants(
            'payment', $startTime, $endTime, false, false);

        $merchantIdChunks = array_chunk($transactedMerchants, 100);
        $merchantIdList   = [];

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            $filteredMerchants = $this->getRepository("transaction")->filterMerchantsWithFirstTransactionBetweenTimestamps(
                $merchantIdChunk, $startTime, $endTime, false);

            if (empty($filteredMerchants) === false)
            {
                $merchantIdList = array_merge($merchantIdList, $filteredMerchants);
            }
        }

        return CollectorDto::create($merchantIdList);
    }

    /**
     * Data will be fetched between (T - 25 hours) to (T - 24 hours)
     * @return float|int
     */
    protected function getStartInterval() : int
    {
        return $this->lastCronTime - (25 * 60 * 60);   // in seconds
    }

    protected function getEndInterval() : int
    {
        return $this->lastCronTime - (24 * 60 * 60);   // in seconds
    }

    public function getPrimaryConnection()
    {
        return Connection::DATA_WAREHOUSE_MERCHANT_LIVE;
    }

    public function getFallbackConnection()
    {
        return Connection::PAYMENT_FETCH_REPLICA_LIVE;
    }
}
