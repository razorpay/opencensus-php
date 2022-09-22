<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;


class SubmerchantFirstTransactionDatalakeCollector extends TimeBoundDbDataCollector
{
    const DATALAKE_QUERY =  "SELECT t.merchant_id FROM hive.realtime_hudi_api.transactions as t JOIN hive.realtime_hudi_api.merchant_access_map as mp ON t.merchant_id = mp.merchant_id AND mp.entity_owner_id IS NOT NULL WHERE t.type ='payment' GROUP BY t.merchant_id HAVING MIN(t.created_at) >= %s AND MIN(t.created_at) < %s";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $dataLakeQuery = sprintf(self::DATALAKE_QUERY, $startTime, $endTime);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'lakeData'      => $lakeData
        ]);

        $merchantIdList   = [];

        foreach ($lakeData as $data)
        {
            $merchantIdList[] = $data['merchant_id'];
        }

        return CollectorDto::create($merchantIdList);
    }

    protected function getStartInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
    }

    protected function getEndInterval(): int
    {
        return Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();
    }
}
