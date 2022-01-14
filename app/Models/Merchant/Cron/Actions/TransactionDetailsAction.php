<?php


namespace RZP\Models\Merchant\Cron\Actions;


use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class TransactionDetailsAction extends BaseAction
{
    public function execute($data = []): ActionDto
    {
        if (empty($data) === true)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $collectorData = $data["transaction_details"]; // since data collector is an array

        $merchantIdChunks = $collectorData->getData();

        if (count($merchantIdChunks) === 0)
        {
            return new ActionDto(Constants::SKIPPED);
        }

        $successCount = 0;
        foreach ($merchantIdChunks as $merchantIdChunk)
        {
            try
            {
                $this->pushTransactionDetailsSegmentEvent($merchantIdChunk);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'merchant_id'   => $merchantIdChunk['merchant_details_merchant_id'],
                    'args'        => $this->args
                ]);
            }
        }

        $this->app['segment-analytics']->buildRequestAndSend();

        if ($successCount === 0)
        {
            $status = Constants::FAIL;
        }
        else
        {
            $status = ($successCount < count($merchantIdChunks)) ? Constants::PARTIAL_SUCCESS : Constants::SUCCESS;
        }

        return new ActionDto($status);
    }

    private function pushTransactionDetailsSegmentEvent($merchantIdChunk)
    {
        $druidData = (new Merchant\Service)->getDataFromDruidForMerchantIds($merchantIdChunk);

        foreach ($druidData as $data)
        {
            $segmentProperties = [
                Merchant\Service::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION => $data[Merchant\Service::SEGMENT_DATA_USER_DAYS_TILL_LAST_TRANSACTION] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV          => $data[Merchant\Service::SEGMENT_DATA_MERCHANT_LIFE_TIME_GMV] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_GMV             => $data[Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_GMV] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_PRIMARY_PRODUCT_USED            => $data[Merchant\Service::SEGMENT_DATA_PRIMARY_PRODUCT_USED] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_PPC                             => $data[Merchant\Service::SEGMENT_DATA_PPC] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS    => $data[Merchant\Service::SEGMENT_DATA_AVERAGE_MONTHLY_TRANSACTIONS] ?: 'NULL',
                Merchant\Service::SEGMENT_DATA_PG_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PG_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PG_ONLY] : 'NULL',
                Merchant\Service::SEGMENT_DATA_PL_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PL_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PL_ONLY] : 'NULL',
                Merchant\Service::SEGMENT_DATA_PP_ONLY                         => isset($data[Merchant\Service::SEGMENT_DATA_PP_ONLY]) ? $data[Merchant\Service::SEGMENT_DATA_PP_ONLY] : 'NULL'
            ];

            $merchantId = $data['merchant_details_merchant_id'];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(),
                Merchant\Balance\Type::PRIMARY);

            if (empty($merchantBalance) === false)
            {
                $segmentProperties[Merchant\Service::SEGMENT_FREE_CREDITS_AVAILABLE] = $merchantBalance->getAmountCredits();
            }

            $this->app['trace']->info(TraceCode::TRANSACTION_DETAILS_CRON_TRACE, [
                'type'          => 'transaction_cron',
                'merchant_id'   => $merchantId,
                'segment_properties'    => $segmentProperties
            ]);

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);
        }
    }
}
