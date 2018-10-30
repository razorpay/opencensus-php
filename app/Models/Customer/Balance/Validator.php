<?php

namespace RZP\Models\Customer\Balance;

use RZP\Base;
use RZP\Constants;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    public function validateBalanceForCredit(int $amount, bool $isRefund = false)
    {
        $newBalance = $this->entity->getBalance() + $amount;

        $maxBalance = $this->entity->getMaxBalance();

        if ($newBalance > $maxBalance)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Maximum wallet payment amount limit has been crossed for the customer');
        }

        // Check usage limits for wallet credits except for refunds
        // if ($isRefund === false)
        // {
        //
        //     31/07/2018: Commenting out the monthly limit validation since there we only
        //     support Closed PPI wallets, and there is no need to validate limits on Closed
        //     instruments. Change done after approval from SHK.
        //
        //     $this->checkMonthlyUsageLimits($newBalance);
        // }
    }

    protected function checkMonthlyUsageLimits(int $newBalance)
    {
        $monthlyUsage = $this->entity->getMonthlyUsage();

        if (($newBalance + $monthlyUsage) > $this->entity->getMaxBalance())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED);
        }
    }

    public function validateBalanceForDebit(int $amount, string $source = Constants\Entity::PAYMENT)
    {
        $newBalance = $this->entity->getBalance() - $amount;

        if ($newBalance < 0)
        {
            // This errorCode will decide the action = TOPUP/PAYOUT on the error message.
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE;

            if ($source == Constants\Entity::PAYOUT)
            {
                $errorCode = ErrorCode::BAD_REQUEST_WALLET_PAYOUT_INSUFFICIENT_BALANCE;
            }

            throw new Exception\BadRequestException($errorCode);
        }
    }
}
