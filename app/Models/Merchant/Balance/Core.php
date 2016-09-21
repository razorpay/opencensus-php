<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Core extends Base\Core
{
    public function checkMerchantBalance($merchant, $amount)
    {
        $balance = $this->repo->balance->getMerchantBalance($merchant);

        if ($balance->getBalance() < $amount)
        {
            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_DEBIT_FAILURE,
                [
                    'message' => 'Not enough balance',
                    'merchant_balance' => $balance->getBalance(),
                    'debit_amount' => $amount
                ]);

            return false;
        }

        return true;
    }
}