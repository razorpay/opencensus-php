<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Trace\TraceCode;

class SalesforceRefinerDataLakeCollector extends MtuDatalakeCollector
{
    const DATALAKE_MERCHANTS_QUERY =  "SELECT partner_id FROM batch_sheets.monthly_partner_mapping WHERE  smep_entp_sales != 'Invalid' AND handover_date >= %s AND handover_date < %s";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $startDate = Carbon::createFromTimestamp($startTime, Timezone::IST)->startOfMonth()->format('Y-m-d');
        $endDate   = Carbon::createFromTimestamp($endTime, Timezone::IST)->endOfMonth()->format('Y-m-d');

        $dataLakeQuery = sprintf(self::DATALAKE_MERCHANTS_QUERY, $startDate, $endDate);

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
        return Carbon::now(Timezone::IST)->startOfMonth()->subMonths(1)->getTimestamp();
    }

    protected function getEndInterval(): int
    {
        return Carbon::now(Timezone::IST)->endOfMonth()->subMonths(1)->getTimestamp();
    }
}
