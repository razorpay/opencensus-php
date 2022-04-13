<?php

namespace RZP\Models\Merchant\Cron\Collectors;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Cron\Collectors\Core\TimeBoundDbDataCollector;
use RZP\Models\Merchant\Cron\Dto\CollectorDto;

class AuthorizedPaymentsMerchantDataCollector extends TimeBoundDbDataCollector
{
    Const DATALAKE_QUERY = "SELECT DISTINCT (payments.merchant_id) FROM hive.realtime_hudi_api.payments
                            JOIN hive.realtime_hudi_api.merchant_details ON merchant_details.merchant_id = payments.merchant_id
                            WHERE merchant_details.activation_status IN ('activated' , 'instantly_activated' , 'activated_mcc_pending')
                            AND payments.status IN ('authorized' , 'captured')
                            AND payments.created_at >= %s";

    protected function collectDataWithinInterval($startTime, $endTime): CollectorDto
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'                  => $this->args,
            'start_time'            => $startTime,
            'end_time'              => $endTime
        ]);

        $merchantIds = $this->getAuthorizedPaymentsMerchants($startTime);

        $this->app['trace']->info(TraceCode::CRON_FETCH_AUTHORIZED_TRANSACTED_MERCHANTS, [
            'start_time'    => $startTime,
        ]);

        return CollectorDto::create($merchantIds);
    }

    protected function getAuthorizedPaymentsMerchants($startTimeStamp)
    {
        $merchantIdLists    = [];

        $dataLakeQuery      = sprintf(self::DATALAKE_QUERY, $startTimeStamp);

        $lakeData           = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        if (empty($lakeData) === false)
        {
            $merchantIdLists = [];

            foreach($lakeData as $merchantData)
            {
                if(empty($merchantData['merchant_id']) === false)
                {
                    $merchantIdLists[] = $merchantData['merchant_id'];
                }
            }

            $this->app['trace']->info(TraceCode::CRON_FETCH_AUTHORIZED_TRANSACTED_MERCHANTS_SUCCESS, [
                'start_time'    => $startTimeStamp
            ]);
        }
        else
        {
            $this->app['trace']->info(TraceCode::CRON_FETCH_AUTHORIZED_TRANSACTED_MERCHANTS_FAILURE, [
                'start_time'    => $startTimeStamp
            ]);
        }

        return $merchantIdLists;
    }

    protected function getStartInterval() : int
    {
        return $this->lastCronTime;
    }

    protected function getEndInterval() : int
    {
        return $this->cronStartTime;
    }
}
