<?php

namespace RZP\Models\Customer\Balance;

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
        $balance = new Entity;

        $customer = $this->repo->customer->findByPublicId($customerId);

        $balanceData = [
            Entity::NAME                => $this->merchant->getName(),
            Entity::BALANCE             => 0,
            Entity::MAX_BALANCE         => self::DEFAULT_MAX_BALANCE,
        ];

        $balance->customer()->associate($customer);

        $balance->merchant()->associate($this->merchant);

        $balance->build($balanceData);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    public function debit(Entity $balance, int $amount)
    {
        $balance->getValidator()->validateBalanceForDebit($balance, $amount);

        $balance->deductBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    public function credit(Entity $balance, int $amount) : Entity
    {
        $balance->getValidator()->validateBalanceForCredit($balance, $amount);

        $balance->addBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    public function fetchOrCreate($customerId) : Entity
    {
        $balance = $this->repo->customer_balance
                        ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        if ($balance !== null and $balance instanceof Entity)
        {
            return $balance;
        }

        // No existing wallet found for the customer ID, create one
        return $this->create($customerId);
    }
}
