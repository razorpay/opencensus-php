<?php

namespace RZP\Models\Customer\Balance;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::BALANCE                 => 'required|integer',
        Entity::MAX_BALANCE             => 'required|integer',
    ];

    public function validateBalanceForCredit(Entity $wallet, int $amount)
    {
        $balance = $wallet->getBalance() + $amount;

        $maxBalance = $wallet->getMaxBalance();

        if ($balance > $maxBalance)
        {
            throw new \Exception('Will cross max_balance', 400);
        }
    }

    public function validateBalanceForDebit(Entity $wallet, int $amount)
    {
        $balance = $wallet->getBalance() - $amount;

        if ($balance < 0)
        {
            throw new \Exception('Balance will go below 0', 400);
        }
    }
}
