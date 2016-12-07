<?php

namespace RZP\Models\Customer\Balance;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::BALANCE                 => 'required|integer',
        Entity::MAX_BALANCE             => 'required|integer',
        Entity::DAILY_USAGE             => 'required|integer',
        Entity::WEEKLY_USAGE            => 'required|integer',
        Entity::MONTHLY_USAGE           => 'required|integer',
    ];

    public function validateBalanceForCredit(Entity $wallet, int $amount)
    {
        $balance = $wallet->getBalance() + $amount;

        $maxBalance = $wallet->getMaxBalance();

        //TODO: Validate for daliy/weekly/monthly usage here

        if ($balance > $maxBalance)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_MAX_AMOUNT_LIMIT_CROSSED_FOR_CUSTOMER);
        }
    }

    public function validateBalanceForDebit(Entity $wallet, int $amount)
    {
        $balance = $wallet->getBalance() - $amount;

        if ($balance < 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE);
        }
    }
}
