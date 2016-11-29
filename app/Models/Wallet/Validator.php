<?php

namespace RZP\Models\Wallet;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::BALANCE                 => 'required|integer',
        Entity::MIN_BALANCE             => 'required|integer',
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

        $minBalance = $wallet->getMinBalance();

        if ($balance < 0 or
            $balance < $minBalance)
        {
            throw new \Exception('Will go below min_balance or 0', 400);
        }
    }
}
