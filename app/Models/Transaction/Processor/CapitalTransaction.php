<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Models\Currency;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;

class CapitalTransaction extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount(abs($this->source->getAmount()));
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
        $this->txn->setGatewayFee(0);
        $this->txn->setGatewayServiceTax(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(\RZP\Models\Transaction\ReconciledType::NA);

        $this->txn->setType($this->source->getType());

        $this->updatePostedDate();
    }

    public function setMerchantFeeDefaults()
    {
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->txn->source->balance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}
