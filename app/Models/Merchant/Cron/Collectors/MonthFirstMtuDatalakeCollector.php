<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;


class MonthFirstMtuDatalakeCollector extends TimeBoundDbDataCollector
{
    const DATALAKE_QUERY =  "WITH added_row_number AS (SELECT *,ROW_NUMBER() OVER(PARTITION BY merchant_id ORDER BY created_at ASC) AS row_number FROM (
    select trn.merchant_id as merchant_id, pt.product as product, trn.created_at as created_at from hive.aggregate_pa.payments_product pt inner join hive.realtime_hudi_api.transactions trn
    ON pt.payment_id=trn.entity_id where trn.type='payment' AND trn.merchant_id NOT IN (SELECT DISTINCT (merchant_id) FROM hive.realtime_hudi_api.transactions
    WHERE type='payment' AND created_at >= %s AND created_at < %s ) AND trn.created_at >= %s AND trn.created_at < %s
    order by merchant_id )
    ) SELECT * FROM added_row_number WHERE row_number = 1 order by merchant_id";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $startOfMonth = $this->getStartOfMonth($startTime);

        $dataLakeQuery = sprintf(self::DATALAKE_QUERY,$startOfMonth,$startTime,$startTime,$endTime);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $merchantsData   = [];

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'start_month'   => $startOfMonth,
            'lakeDataSize'      => sizeof($lakeData)
        ]);

        foreach ($lakeData as $data)
        {
            array_push($merchantsData,[$data['merchant_id'], $data['product']]);
        }

        return CollectorDto::create($merchantsData);
    }

    protected function getStartOfMonth($startTime)
    {
        return Carbon::createFromTimestamp($startTime,Timezone::IST)->startOfMonth()->startOfDay()->getTimestamp();
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
