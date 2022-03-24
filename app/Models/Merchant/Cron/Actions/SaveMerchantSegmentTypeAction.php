<?php

namespace RZP\Models\Merchant\Cron\Actions;

use Cache;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Constants as MerchantConstants;

class SaveMerchantSegmentTypeAction extends BaseAction
{
    const PREFIX = 'merchant_segment_type';

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

        $successCount = 0;

        foreach ($merchantIdList as $merchantId)
        {
            try
            {
                $this->saveMerchantAuthorisedTransactionCountInLastMonth($merchantId);

                $successCount++;
            }
            catch (\Throwable $ex)
            {
                $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_ACTION_FAILURE, [
                    'args'        => $this->args,
                    'merchant_id' => $merchantId
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

    private function saveMerchantAuthorisedTransactionCountInLastMonth($merchantId)
    {
        $startTimeStamp     = $this->args['start_time'];

        $totalPaymentsCount = $this->repo->payment->getTotalAuthorizedPaymentCountOfMerchant($merchantId, $startTimeStamp);

        $this->app['cache']->set($this->getCacheKey($merchantId), $totalPaymentsCount, 60*60*24*2);
    }

    protected function getCacheKey(string $merchantId)
    {
        return self::PREFIX . ':' . $merchantId;
    }
}
