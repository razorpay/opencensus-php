<?php

namespace RZP\Models\Customer\Balance;

use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Models\Base;
use RZP\Models\Wallet;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Customer;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    /**
     * Create and save a new customer_balance wallet account linked to merchant
     *
     * @param  Customer\Entity $customer
     * @param  Merchant\Entity $merchant
     * @return Entity              Balance Entity
     */
    protected function create(Customer\Entity $customer, Merchant\Entity $merchant) : Entity
    {
        $this->validateIndianContact($customer->getContact());

        $balance = new Entity;

        $balance->customer()->associate($customer);

        $balance->merchant()->associate($merchant);

        $balance->generate([]);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Debit an amount from customer_balance account
     *
     * @param  string $customerId
     * @param  int    $amount
     * @return Entity
     */
    public function debit(string $customerId, int $amount) : Entity
    {
        $balance = $this->repo
                        ->customer_balance
                        ->lockForUpdate($customerId);

        $balance->getValidator()->validateBalanceForDebit($amount);

        $balance->deductBalance($amount);

        $this->repo->saveOrFail($balance);

        return $balance;
    }

    /**
     * Credit an amount to customer_balance account
     *
     * @param Entity $balance
     * @param int    $amount
     * @param bool   $isRefund
     *
     * @return Entity
     */
    public function credit(Entity $balance, int $amount, bool $isRefund = false) : Entity
    {
        $balance->getValidator()->validateBalanceForCredit($amount);

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
                        ->findByCustomerAndMerchantSilent($customer, $merchant);

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
        $balance = $this->repo
                        ->customer_balance
                        ->lockForUpdate($customerId);

        return $this->credit($balance, $amount, true);
    }

    /**
     * Wallets can only be created for customers having
     * Indian numbers
     *
     * @param   string        $number
     * @return  null
     * @throws  Exception\BadRequestException
     */
    protected function validateIndianContact(string $number)
    {
        if (empty($number) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        $number = new PhoneBook($number, true);

        $country = $number->getRegionCodeForNumber();

        if ($country !== 'IN')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED);
        }
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

        $lastTxnTime = Carbon::createFromTimestamp($lastTxnTime, 'Asia/Kolkata');

        list($resetDay, $resetWeek, $resetMonth) = $this->checkTimestampForReset($lastTxnTime);

        $this->updateDailyUsage($balance, $amount, $resetDay);

        $this->updateWeeklyUsage($balance, $amount, $resetWeek);

        $this->updateMonthlyUsage($balance, $amount, $resetMonth);
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

    protected function updateWeeklyusage(Entity $balance, int $amount, bool $resetWeek)
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
