<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class CreditRepayment extends Base
{
    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setGatewayServiceTax(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(\RZP\Models\Transaction\ReconciledType::NA);

        $this->updatePostedDate();
    }

    public function setMerchantFeeDefaults()
    {
        return ;
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    function calculateFees()
    {
        $amount = $this->source->getAmount();

        $this->debit = abs($amount);
    }
}
