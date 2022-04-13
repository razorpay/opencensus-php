<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Cache;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Service as MerchantService;

class SaveMerchantAuthorizedTransactionCount extends BaseAction
{
    const DATALAKE_QUERY =  "SELECT payments.merchant_id, count(*) as transaction_count FROM hive.realtime_hudi_api.payments WHERE status IN ('authorized', 'captured') AND merchant_id IN (%s) AND updated_at >= %s group by payments.merchant_id";

    const MERCHANT_IDS_CHUNK_SIZE = 500;

    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData  = $data["authorized_payments_merchants"];

        $merchantIdList = $collectorData->getData();

        if (count($merchantIdList) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount       = 0;

        $startTimeStamp     = $this->args['start_time'];

        $merchantIdChunks   = array_chunk($merchantIdList, self::MERCHANT_IDS_CHUNK_SIZE);

        $this->app['trace']->info(TraceCode::CRON_ACTION_AUTHORIZED_TRANSACTION_COUNT, [
            'start_time'     => $startTimeStamp,
            'total_chunks'   => count($merchantIdChunks),
        ]);

        foreach ($merchantIdChunks as $index => $merchantIdChunk)
        {
            try {
                $start              = millitime();

                $startTimeStamp     = $this->args['start_time'];

                $this->saveMerchantsTransactionCount($merchantIdChunk, $startTimeStamp, $index, $successCount);

                $this->app['trace']->info(TraceCode::CRON_ACTION_AUTHORIZED_TRANSACTION_MERCHANT_IDS_CHUNK_SUCCESS, [
                    'completed_chunk_index'   => $index,
                    'start_time_stamp'        => $startTimeStamp,
                    'duration'                => millitime() - $start
                ]);
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'failed_chunk_index'      => $index,
                    'start_time_stamp'        => $startTimeStamp,
                    'duration'                => millitime() - $start
                ]);
            }
        }

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIdList)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    protected function saveMerchantsTransactionCount($merchantIdChunk, $startTimeStamp, $chunkIndex, &$successCount)
    {
        $strMerchantIds     = implode(', ', array_map(function ($val) { return sprintf('\'%s\'', $val);}, $merchantIdChunk));

        $dataLakeQuery      = sprintf(self::DATALAKE_QUERY, $strMerchantIds, $startTimeStamp);

        $lakeData           = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        if (empty($lakeData) === false)
        {
            foreach ($lakeData as $merchantData)
            {
                $merchantId       = $merchantData['merchant_id'];
                $transactionCount = $merchantData['transaction_count'];

                $this->setMerchantAuthorisedTransactionCountForLastMonth($merchantId, $transactionCount);

                $successCount      += 1;
            }
        }
        else
        {
            $this->app['trace']->info(TraceCode::CRON_ACTION_AUTHORIZED_TRANSACTION_MERCHANT_IDS_CHUNK_FAILURE, [
                'start_time'    => $startTimeStamp,
                'chunk_index'   => $chunkIndex
            ]);
        }
    }

    protected function setMerchantAuthorisedTransactionCountForLastMonth($merchantId, $totalPaymentsCount)
    {
        if (empty($totalPaymentsCount) === false)
        {
            $this->app['cache']->set((new MerchantService())->getMerchantTransactionsInLastMonthKey($merchantId), $totalPaymentsCount, 60*60*24*2);
        }
    }
}
