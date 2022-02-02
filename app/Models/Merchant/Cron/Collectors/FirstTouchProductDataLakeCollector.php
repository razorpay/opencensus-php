<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use Database\Connection;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Traits\ConnectionFallbackMechanism;
use RZP\Trace\TraceCode;

class FirstTouchProductDataLakeCollector extends MtuDatalakeCollector
{
    const FIRST_TOUCH_PRODUCT_QUERY = "select * from hive.aggregate_pa.payments_product where merchant_id in (%s) and first_txn = 1";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $mtuTransactedCollector = parent::collectDataWithinInterval($startTime, $endTime);

        $merchantIds = $mtuTransactedCollector->getData();

        if(empty($merchantIds) === true)
        {
            return CollectorDto::create([]);
        }

        $strMerchantIds = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIds));

        $dataLakeQuery = sprintf(self::FIRST_TOUCH_PRODUCT_QUERY, $strMerchantIds);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

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
