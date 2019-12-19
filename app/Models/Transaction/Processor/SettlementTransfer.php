<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;

class SettlementTransfer extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount($this->source->getAmount());

        $this->txn->setChannel($this->source->merchant->getChannel());
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $this->credit = $this->source->getAmount();
    }

    public function updateTransaction()
    {
        // TODO: calculate the settledAt from schedule
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setApiFee(0);

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}
