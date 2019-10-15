<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Channel;

class Commission extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount(abs($this->getNetAmount()));

        // commission transactions will be on hold by default
        $this->txn->setOnHold(true);
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $netAmount = $this->getNetAmount();

        if ($netAmount > 0)
        {
            $this->credit = $netAmount;
        }
        else if ($netAmount < 0)
        {
            $this->debit = -1 * $netAmount;
        }
    }

    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        // we want to settle commissions to partner from yes_bank nodal account by default
        $attributes = [
            Transaction\Entity::SETTLED_AT => $settledAt,
            Transaction\Entity::CHANNEL    => Channel::YESBANK,
        ];

        $this->txn->fill($attributes);

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $merchant = $this->source->merchant;

        $this->merchantBalance = (new Balance\Core)->createOrFetchCommissionBalance($merchant, $this->mode);

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    protected function getNetAmount()
    {
        $credit = $this->source->getCredit();
        $debit  = $this->source->getDebit();

        return ($credit - $debit);
    }
}
