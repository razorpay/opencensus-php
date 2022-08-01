<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class MerchantPostFirstTransactionEventDataLakeCollector extends TimeBoundDbDataCollector
{
    const DATALAKE_QUERY =  "select id from hive.aggregate_pa.merchant_fact where first_txn_date is not null and date_add('day', 30, date_parse(first_txn_date, '%Y-%m-%d')) =  current_date";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $dataLakeQuery = self::DATALAKE_QUERY;

        $merchantIdList = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $data["merchantIds"] = $merchantIdList;

        return CollectorDto::create($data);
    }

    /**
     * Data will be fetched between (D - 30 day morning) to (D - 30 night)
     * @return float|int
     */
    protected function getStartInterval(): int
    {
        return Carbon::today(Timezone::IST)->subDays(30)->startOfDay()->getTimestamp();
    }

    protected function getEndInterval(): int
    {
        return Carbon::today(Timezone::IST)->subDays(30)->endOfDay()->getTimestamp();
    }
}
