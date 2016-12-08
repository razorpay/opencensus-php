<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Wallet;
use RZP\Models\Transaction;
use RZP\Models\Customer;

class Core extends Base\Core
{
    /**
     * Create and save a new customer_balance record for a customer <> merchant
     *
     * @param  string  $customerId
     * @return Entity              Balance Entity
     */
    protected function create($customerId) : Entity
    {
        $balance = new Entity;

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);

        $balance->customer()->associate($customer);

        $balance->merchant()->associate($this->merchant);

        $balance->build();

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Debit an amount from customer_balance
     *
     * @param  Entity $balance
     * @param  int    $amount
     * @return Entity
     */
    public function debit(Entity $balance, int $amount) : Entity
    {
        $balance->getValidator()->validateBalanceForDebit($balance, $amount);

        $balance->deductBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Credit an amount from customer_balance
     *
     * @param  Entity $balance
     * @param  int    $amount
     * @return Entity
     */
    public function credit(Entity $balance, int $amount, bool $isRefund = false) : Entity
    {
        $balance->getValidator()->validateBalanceForCredit($balance, $amount);

        $balance->addBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }


    /**
     * Fetches or creates and returns a customer_balance entity for a merchant-customer pair
     *
     * @param  string $customerId
     * @return Entity
     */
    public function fetchOrCreate(string $customerId) : Entity
    {
        $balance = $this->repo->customer_balance
                        ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        if (($balance !== null) and
            ($balance instanceof Entity))
        {
            return $balance;
        }

        // No existing wallet found for the customer ID linked
        // to the current merchant, create one instead
        return $this->create($customerId);
    }

    /**
     * Refund an amount to customer_balance, lock and credit
     *
     * @param  string $customerId
     * @param  int    $amount
     * @return Entity
     */
    public function refund(string $customerId, int $amount) : Entity
    {
        $balance = $this->repo->customer_balance
                        ->getCustomerBalanceLockForUpdate($customerId, $this->merchant);

        return $this->credit($balance, $amount, truerue);
    }
}
