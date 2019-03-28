<?php

namespace RZP\Models\Customer\Balance;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Core extends Base\Core
{
    /**
     * Debit an amount from customer_balance account
     *
     * @param string $customerId
     * @param int    $amount
     * @param string $source
     *
     * @return Entity
     */
    public function debit(string $customerId, int $amount, string $source) : Entity
    {
        $balance = $this->repo
                        ->customer_balance
                        ->lockForUpdate($customerId);

        $balance->getValidator()->validateBalanceForDebit($amount, $source);

        $balance->deductBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Credit an amount to customer_balance account
     *
     * @param string $customerId
     * @param int    $amount
     * @param bool   $isRefund
     *
     * @return Entity
     */
    public function credit(string $customerId, int $amount, bool $isRefund = false) : Entity
    {
        $balance = $this->repo->customer_balance->lockForUpdate($customerId);

        //
        // 11/10/2018: Decided to stop all validation on wallet max balance, since we will only
        // support closed PPI wallets for the foreseeable future.
        //
        // $balance->getValidator()->validateBalanceForCredit($amount);

        $balance->addBalance($amount);

        //
        // For all credits to the wallet, other than refunds,
        // update daily/weekly/month usage values
        //
        if ($isRefund === false)
        {
            $this->updateUsages($balance, $amount);

            $balance->setLastLoadedAt(time());
        }

        $this->repo->saveOrFail($balance);

        return $balance;
    }


    /**
     * Fetches, or creates and returns, a customer_balance entity for a merchant
     *
     * @param  Customer\Entity $customer
     * @param  Merchant\Entity $merchant
     * @return Entity
     */
    public function fetchOrCreate(Customer\Entity $customer, Merchant\Entity $merchant) : Entity
    {
        $balance = $this->repo
                        ->customer_balance
                        ->findByCustomerIdAndMerchantSilent($customer->getId(), $merchant, true);

        if ($balance !== null)
        {
            return $balance;
        }

        //
        // No existing wallet found for the customer ID linked
        // to the current merchant, create one instead
        //
        return $this->create($customer, $merchant);
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
        return $this->credit($customerId, $amount, true);
    }

    /**
     * Create and save a new customer_balance wallet account linked to merchant
     *
     * @param  Customer\Entity $customer
     * @param  Merchant\Entity $merchant
     * @return Entity              Balance Entity
     */
    protected function create(Customer\Entity $customer, Merchant\Entity $merchant) : Entity
    {
        $customer->getValidator()->validateIndianContact();

        $balance = new Entity;

        $balance->customer()->associate($customer);

        $balance->merchant()->associate($merchant);

        $balance->build();

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Update usage limits for the balance entity
     *
     * @param  Entity $balance
     * @param  int    $amount
     */
    protected function updateUsages(Entity $balance, int $amount)
    {
        $lastTxnTime = $balance->getLastLoadedAt();

        //
        // No previous transaction on the wallet
        // (shouldn't happen - entity is created on first credit)
        //
        if ($lastTxnTime === null)
        {
            return $this->resetAllUsages($balance, $amount);
        }

        $lastTxnTime = Carbon::createFromTimestamp($lastTxnTime, Timezone::IST);

        $resetParams = $this->checkTimestampForReset($lastTxnTime);

        $this->updateDailyUsage($balance, $amount, $resetParams['resetDay']);

        $this->updateWeeklyUsage($balance, $amount, $resetParams['resetWeek']);

        $this->updateMonthlyUsage($balance, $amount, $resetParams['resetMonth']);
    }

    protected function checkTimestampForReset(Carbon $lastTxnTime) : array
    {
        $now = Carbon::now(Timezone::IST);

        $resetDay = $resetWeek = $resetMonth = false;

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

        return [
            'resetDay'      => $resetDay,
            'resetWeek'     => $resetWeek,
            'resetMonth'    => $resetMonth,
        ];
    }

    protected function resetAllUsages(Entity $balance, int $amount)
    {
        $balance->setDailyUsage($amount);

        $balance->setWeeklyUsage($amount);

        $balance->setMonthlyUsage($amount);
    }

    protected function updateDailyUsage(Entity $balance, int $amount, bool $resetDay)
    {
        if ($resetDay === false)
        {
            $amount = $balance->getDailyUsage() + $amount;
        }

        $balance->setDailyUsage($amount);
    }

    protected function updateWeeklyUsage(Entity $balance, int $amount, bool $resetWeek)
    {
        if ($resetWeek === false)
        {
            $amount = $balance->getWeeklyUsage() + $amount;
        }

        $balance->setWeeklyUsage($amount);
    }

    protected function updateMonthlyUsage(Entity $balance, int $amount, bool $resetMonth)
    {
        if ($resetMonth === false)
        {
            $amount = $balance->getMonthlyUsage() + $amount;
        }

        $balance->setMonthlyUsage($amount);
    }
}
