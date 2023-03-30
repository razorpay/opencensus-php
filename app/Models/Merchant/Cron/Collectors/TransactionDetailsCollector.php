<?php


namespace RZP\Models\Merchant\Cron\Collectors;


use Carbon\Carbon;
use Database\Connection;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;
use RZP\Models\Merchant\Cron\Traits\ConnectionFallbackMechanism;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Models\Base\UniqueIdEntity;

class TransactionDetailsCollector extends TimeBoundDbDataCollector
{
    use ConnectionFallbackMechanism;

    const DATALAKE_QUERY = "SELECT distinct(merchant_id) FROM hive.realtime_hudi_api.transactions WHERE type='payment' AND created_at BETWEEN %s AND %s";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $dataLakeQuery = sprintf(self::DATALAKE_QUERY, $startTime, $endTime);

        $transactedMerchants = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery, false);

        $this->app['trace']->info(TraceCode::CRON_DATA_COLLECTOR_TRACE, [
            'args'          => $this->args,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'merchant_ids'  => count($transactedMerchants)
        ]);

        $flattenedTransactedMerchants = array_map( function ($element){ return $element[0];}, $transactedMerchants);

        $merchantIdChunks = array_chunk($flattenedTransactedMerchants, 1000);

        $merchantData =  $this->getDataForMerchants($merchantIdChunks);

        return CollectorDto::create($merchantData);
    }

    /**
     * Data will be fetched between (T - 25 hours) to (T - 24 hours)
     * @return float|int
     */
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

    protected function getDataForMerchants($merchantIdChunks) : array
    {
        $merchantDataFromDruid = [];

        $experimentResult       = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            RazorxTreatment::DRUID_MIGRATION,
            Mode::LIVE);

        $isDruidMigrationEnabled = ( $experimentResult === 'on' ) ? true : false;

        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            try
            {
                if($isDruidMigrationEnabled === true)
                {
                    $merchantData = (new MerchantService)->getDataFromPinotForMerchantIds($merchantIdChunk);
                }
                else
                {
                    $merchantData = (new MerchantService)->getDataFromDruidForMerchantIds($merchantIdChunk);
                }

                array_push($merchantDataFromDruid, $merchantData);
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'merchant_ids'   => $merchantIdChunk,
                ]);
            }
        }

        return $merchantDataFromDruid;
    }
}
