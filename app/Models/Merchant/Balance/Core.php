<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Core extends Base\Core
{
    /**
     * Check that a merchant's balance is greater than amount argument passed
     *
     * @param  Merchant\Entity $merchant
     * @param  int             $amount
     * @return bool
     */
    public function checkMerchantBalance(Merchant\Entity $merchant, int $amount) : bool
    {
        $balance = $this->repo->balance->getMerchantBalance($merchant);

        if ($balance->getBalance() < $amount)
        {
            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE,
                [
                    'message'           => 'Not enough balance',
                    'merchant_balance'  => $balance->getBalance(),
                    'debit_amount'      => $amount
                ]);

            return false;
        }

        return true;
    }
}
