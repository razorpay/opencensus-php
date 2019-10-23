<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;

class Settlement extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount($this->source->getAmount());

        $this->txn->setChannel($this->source->getChannel());
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $this->debit = $this->source->getAmount();
    }

    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettled(true);
        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setApiFee(0);

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}
