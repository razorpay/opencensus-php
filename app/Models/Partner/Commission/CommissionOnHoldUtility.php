<?php

namespace RZP\Models\Partner\Commission;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Bucket;

class CommissionOnHoldUtility extends Base\Service
{
    public function dispatchForSettlement(Transaction\Entity $txn, array $successTxnIds)
    {
        // dispatch for settlement bucketing if at least one commission transaction on hold is cleared
        try
        {
            if (empty($txn) === false)
            {
                $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

                $txn->setSettledAt($settledAt);

                $bucketCore = new Bucket\Core;

                $balance = $txn->accountBalance;

                $newService = $bucketCore->shouldProcessViaNewService($txn->getMerchantId(), $balance);

                if ($newService === true)
                {
                    $bucketCore->settlementServiceToggleTransactionHold($successTxnIds, null);
                }
                else
                {
                    (new Transaction\Core)->dispatchForSettlementBucketing($txn);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Logger::CRITICAL, TraceCode::COMMISSION_TRANSACTIONS_DISPATCH_TO_SETTLEMENTS_BUCKET_FAILURE);

            $this->trace->count(Partner\Metric::COMMISSION_TRANSACTIONS_SETTLEMENTS_DISPATCH_FAILED_TOTAL);

            throw $e;
        }
    }
}