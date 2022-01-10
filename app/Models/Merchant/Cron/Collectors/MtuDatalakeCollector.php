<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class MtuDatalakeCollector extends TimeBoundDbDataCollector
{
    const DATALAKE_QUERY =  "SELECT merchant_id FROM hive.realtime_hudi_api.transactions WHERE type='payment' GROUP BY merchant_id HAVING MIN(created_at) >= %s AND MIN(created_at) < %s";

    protected $name = "mtu_transacted_merchants";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $dataLakeQuery = sprintf(self::DATALAKE_QUERY, $startTime, $endTime);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $merchantIdList   = [];

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'lakeData'      => $lakeData
        ]);

        foreach ($lakeData as $data)
        {
            $merchantIdList[] = $data['merchant_id'];
        }

        return CollectorDto::create($merchantIdList);
    }

    protected function getStartInterval(): int
    {
        return $this->lastCronTime - (1 * 60 * 60); // in seconds
    }

    protected function getEndInterval(): int
    {
        return Carbon::now()->getTimestamp() - (1 * 60 * 60); // in seconds
    }
}
