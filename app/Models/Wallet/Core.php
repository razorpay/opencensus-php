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

    protected function create($customerId) : Entity
    {
        $wallet = new Entity;

        $customer = $this->repo->customer->findByPublicId($customerId);

        $walletData = [
            Entity::NAME                => $this->merchant->getName(),
            Entity::BALANCE             => 0,
            Entity::MIN_BALANCE         => self::DEFAULT_MIN_BALANCE,
            Entity::MAX_BALANCE         => self::DEFAULT_MAX_BALANCE,
        ];

        $wallet->customer()->associate($customer);

        $wallet->merchant()->associate($this->merchant);

        $wallet->build($walletData);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function debit(Entity $wallet, int $amount)
    {
        $wallet->getValidator()->validateBalanceForDebit($wallet, $amount);

        $wallet->deductBalance($amount);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function credit(Entity $wallet, int $amount) : Entity
    {
        $wallet->getValidator()->validateBalanceForCredit($wallet, $amount);

        $wallet->addBalance($amount);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function fetchOrCreate($customerId) : Entity
    {
        $wallet = $this->repo->wallets->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        if ($wallet !== null and $wallet instanceof Entity)
        {
            return $wallet;
        }

        // No existing wallet found for the customer ID, create one
        return $this->create($customerId);
    }
}
