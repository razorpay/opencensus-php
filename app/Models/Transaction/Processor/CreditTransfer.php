<?php


namespace RZP\Models\Transaction\Processor;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Transaction\ReconciledType;

class CreditTransfer extends Base
{
    public function fillDetails()
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

        $this->txn->setAmount(abs($this->source->getAmount()));

        $this->txn->setChannel($this->source->getChannel());
    }

    public function updateTransaction()
    {
        $settledAt = $reconciledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);

        $this->txn->setReconciledAt($reconciledAt);

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setGatewayFee(0);

        $this->txn->setGatewayServiceTax(0);

        $this->updatePostedDate();

        $this->repo->saveOrFail($this->txn);
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {

    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we back fill creditTransfer
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}
