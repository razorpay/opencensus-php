<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use Database\Connection;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Traits\ConnectionFallbackMechanism;
use RZP\Trace\TraceCode;

class SignupWebAttributionDataCollector extends TimeBoundDbDataCollector
{
    const DATA_LAKE_WEB_ATTRIBUTION_QUERY   = " SELECT distinct (m.id), pam.device, pam.acquisition_medium, pam.acquisition_source, pam.acquisition_campaign, pam.acquisition_keyword, pam.acquisition_adgroup, pam.last_acquisition_source, pam.last_acquisition_medium, pam.last_acquisition_campaign, pam.last_acquisition_keyword, pam.last_acquisition_adgroup, pam.landing_page, pam.last_landing_page " .
    " FROM hive.realtime_hudi_api.merchants m " .
    " Join hive.aggregate_pa.mid_attribution pam on m.id = pam.mid where " .
    " pam.acquisition_medium is not null and " .
    " m.created_at > %s AND m.created_at < %s";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $dataLakeQuery = sprintf(self::DATA_LAKE_WEB_ATTRIBUTION_QUERY, $startTime, $endTime);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'lakeDataCount' => count($lakeData)
        ]);

        return CollectorDto::create($lakeData);
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
