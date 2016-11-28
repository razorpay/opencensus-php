<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;
use RZP\Models\Wallet;
use RZP\Models\Transaction;
use RZP\Models\Customer;

class Core extends Base\Core
{
    const DEFAULT_MIN_BALANCE       = 0;
    const DEFAULT_MAX_BALANCE       = 1000000;

    public function create($customerId, array $input)
    {
        $wallet = new Entity;

        $customerId = Entity::verifyIdAndSilentlyStripSign($customerId);

        $walletData = [
            Entity::CUSTOMER_ID         => $customerId,
            Entity::NAME                => $this->merchant->getName(),
            Entity::BALANCE             => 0,
            Entity::MIN_BALANCE         => self::DEFAULT_MIN_BALANCE,
            Entity::MAX_BALANCE         => self::DEFAULT_MAX_BALANCE,
        ];

        $wallet->build($walletData);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function debit()
    {
        ;
    }

    public function credit(Entity $wallet, int $amount)
    {
        $wallet->getValidator()->validateBalanceForCredit($wallet, $amount);

        $wallet->addBalance($amount);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function fetchOrCreate($customerId, $input)
    {
        $wallet = $this->fetchByCustomerId($customerId);

        if ($wallet !== null and $wallet instanceof Entity)
        {
            return $wallet;
        }

        // No existing wallet found for the customer ID, create one
        return $this->create($customerId, $input);
    }

    public function fetchByCustomerId(string $customerId)
    {
        return $this->repo->wallets->findByCustomerId($customerId);
    }
}
