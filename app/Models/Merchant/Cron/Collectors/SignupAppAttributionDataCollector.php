<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use Carbon\Carbon;
use Database\Connection;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Traits\ConnectionFallbackMechanism;
use RZP\Trace\TraceCode;

class SignupAppAttributionDataCollector extends TimeBoundDbDataCollector
{
    use ConnectionFallbackMechanism;

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime
        ]);

        $appAtrributionData = $this->getRepository("app_attribution_detail")->fetchAppAttributionForMerchantsCreatedBetween($startTime,$endTime);

        return CollectorDto::create($appAtrributionData);
    }


    protected function getStartInterval() : int
    {
        return Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
    }

    protected function getEndInterval() : int
    {
        return Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();
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
