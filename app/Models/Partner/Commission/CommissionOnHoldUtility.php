<?php

namespace RZP\Models\Partner\Commission;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Settlement\Bucket;

class CommissionOnHoldUtility
{

    public function dispatchForSettlement(Transaction\Entity $txn, array $successTxnIds)
    {
        // dispatch for settlement bucketing if at least one commission transaction on hold is cleared
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
}