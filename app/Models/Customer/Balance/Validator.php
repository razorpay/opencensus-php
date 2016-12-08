<?php

namespace RZP\Models\Customer\Balance;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    public function validateBalanceForCredit(Entity $wallet, int $amount, bool $isRefund = false)
    {
        $newBalance = $wallet->getBalance() + $amount;

        $maxBalance = $wallet->getMaxBalance();

        if ($newBalance > $maxBalance)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_MAX_AMOUNT_LIMIT_CROSSED_FOR_CUSTOMER);
        }

        if ($isRefund === false)
        {
            $this->checkMonthlyUsageLimits($wallet, $newBalance);
        }

    }

    protected function checkMonthlyUsageLimits(Entity $wallet, int $newBalance)
    {
        $monthlyUsage = $wallet->getMonthlyUsage();

        if ($newBalance + $monthlyUsage > $wallet->getMaxBalance())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED);
        }
    }

    public function validateBalanceForDebit(Entity $wallet, int $amount)
    {
        $newBalance = $wallet->getBalance() - $amount;

        if ($newBalance < 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
        }
    }
}
