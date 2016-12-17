<?php

namespace RZP\Models\Customer\Balance;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Wallet;
use RZP\Models\Transaction;
use RZP\Models\Customer;

class Core extends Base\Core
{
    /**
     * Create and save a new customer_balance record for a customer <> merchant
     *
     * @param  Customer\Entity $customer
     * @return Entity              Balance Entity
     */
    protected function create(Customer\Entity $customer) : Entity
    {
        $balance = new Entity;

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

        if ($isRefund === false)
        {
            $balance = $this->updateUsages($balance, $amount);
        }

        $this->repo->saveOrFail($balance);

        return $balance;
    }


    /**
     * Fetches or creates and returns a customer_balance entity for a merchant-customer pair
     *
     * @param  Customer\Entity $customer
     * @return Entity
     */
    public function fetchOrCreate(Customer\Entity $customer) : Entity
    {
        $balance = $this->repo
                        ->customer_balance
                        ->findByIdAndMerchantSilent($customer->getId(), $this->merchant);

        if ($balance !== null)
        {
            return $balance;
        }

        // No existing wallet found for the customer ID linked
        // to the current merchant, create one instead
        return $this->create($customer);
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
        $balance = $this->repo
                        ->customer_balance
                        ->getCustomerBalanceLockForUpdate($customerId);

        return $this->credit($balance, $amount, true);
    }

    protected function updateUsages(Entity $balance, int $amount)
    {
        $lastTxnTime = (new Customer\Transaction\Core)->getLastCreditTransactionTime($balance);

        // No previous transaction on the wallet
        if ($lastTxnTime === null)
        {
            return $this->resetAllUsages($balance, $amount);
        }

        list($resetDay, $resetWeek, $resetMonth) = $this->checkTimestampForReset($lastTxnTime);

        // Daily/ Weekly limits are not being enforced right now,
        // leaving the code commented - for future use

        // $balance = $this->updateDailyUsage($balance, $amount, $resetDay);

        // $balance = $this->updateWeeklyUsage($balance, $amount, $resetWeek);

        $balance = $this->updateMonthlyUsage($balance, $amount, $resetMonth);

        return $balance;
    }

    protected function checkTimestampForReset(Carbon $lastTxnTime) : array
    {
        $now = Carbon::now('Asia/Kolkata');

        $resetDay = false;

        $resetWeek = false;

        $resetMonth = false;

        if ($lastTxnTime->dayOfYear !== $now->dayOfYear)
        {
            $resetDay = true;
        }

        if ($lastTxnTime->weekOfYear !== $now->weekOfYear)
        {
            $resetWeek = true;
        }

        if ($lastTxnTime->month !== $now->month)
        {
            $resetMonth = true;
        }

        return [$resetDay, $resetWeek, $resetMonth];
    }

    protected function resetAllUsages(Entity $balance, int $amount) : Entity
    {
        // $balance->setDailyUsage($amount);

        // $balance->setWeeklyUsage($amount);

        $balance->setMonthlyUsage($amount);

        return $balance;
    }

    // Unused
    protected function updateDailyUsage(Entity $balance, int $amount, bool $resetDay) : Entity
    {
        if ($resetDay === false)
        {
            $amount = $balance->getDailyUsage() + $amount;
        }

        $balance->setDailyUsage($amount);

        return $balance;
    }

    // Unused
    protected function updateWeeklyusage(Entity $balance, int $amount, bool $resetWeek) : Entity
    {
        if ($resetWeek === false)
        {
            $amount = $balance->getWeeklyUsage() + $amount;
        }

        $balance->setWeeklyUsage($amount);

        return $balance;
    }

    protected function updateMonthlyUsage(Entity $balance, int $amount, bool $resetMonth) : Entity
    {
        if ($resetMonth === false)
        {
            $amount = $balance->getMonthlyUsage() + $amount;
        }

        $balance->setMonthlyUsage($amount);

        return $balance;
    }
}
