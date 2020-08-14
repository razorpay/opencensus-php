<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Transaction;

class Adjustment extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount(abs($this->source->getAmount()));

        $this->txn->setChannel($this->source->getChannel());
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $amount = $this->source->getAmount();

        if ($amount > 0)
        {
            $this->credit = $amount;
        }

        if ($amount < 0)
        {
            $this->debit = abs($amount);
        }
    }

    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(Transaction\ReconciledType::NA);

        $this->updatePostedDate();

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we back fill adjustment balance_id
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}
