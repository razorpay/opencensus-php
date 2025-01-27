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
        $countryCode = $this->txn->merchant->getCountry();
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();
        $channel = Constants::COUNTRY_CODE_TO_TRANSACTION_CHANNEL_MAP[$countryCode];

        // We want to settle commissions to partner from yes_bank nodal account by default if not present for merchant country
        if ($channel === null)
        {
            $channel = Channel::YESBANK;
        }

        $attributes = [
            Transaction\Entity::SETTLED_AT => $settledAt,
            Transaction\Entity::CHANNEL    => $channel,
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
