<?php

namespace RZP\Models\Customer\Balance;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;

/**
 * Class Entity
 *
 * @package RZP\Models\Customer\Balance
 *
 * @property \RZP\Models\Customer\Entity $customer
 */
class Entity extends Base\PublicEntity
{
    const CUSTOMER_ID       = 'customer_id';
    const MERCHANT_ID       = 'merchant_id';
    const BALANCE           = 'balance';
    const DAILY_USAGE       = 'daily_usage';
    const WEEKLY_USAGE      = 'weekly_usage';
    const MONTHLY_USAGE     = 'monthly_usage';
    const MAX_BALANCE       = 'max_balance';
    const LAST_LOADED_AT    = 'last_loaded_at';

    protected $entity = 'customer_balance';

    protected $primaryKey = self::CUSTOMER_ID;

    const MAX_BALANCE_DEFAULT = 2000000;

    protected $fillable = [
        self::BALANCE,
        self::DAILY_USAGE,
        self::WEEKLY_USAGE,
        self::MONTHLY_USAGE,
        self::MAX_BALANCE,
    ];

    protected $visible = [
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::BALANCE,
        self::DAILY_USAGE,
        self::WEEKLY_USAGE,
        self::MONTHLY_USAGE,
        self::MAX_BALANCE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::BALANCE,
        self::MONTHLY_USAGE,
        self::MAX_BALANCE,
    ];

    protected $defaults = [
        self::BALANCE       => 0,
        self::MAX_BALANCE   => null,
        self::DAILY_USAGE   => 0,
        self::WEEKLY_USAGE  => 0,
        self::MONTHLY_USAGE => 0,
    ];

    protected $publicSetters = [
        self::CUSTOMER_ID,
        self::ENTITY,
        self::MONTHLY_USAGE,
    ];

    protected $casts = [
        self::BALANCE           => 'int',
        self::MAX_BALANCE       => 'int',
        self::DAILY_USAGE       => 'int',
        self::WEEKLY_USAGE      => 'int',
        self::MONTHLY_USAGE     => 'int',
    ];

    protected $amounts = [
        self::BALANCE,
        self::MAX_BALANCE,
        self::DAILY_USAGE,
        self::WEEKLY_USAGE,
        self::MONTHLY_USAGE,
    ];

    // -------------------- Relations ---------------------------

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------- End Relations -----------------------

    public function setBalance(int $balance)
    {
        if ($balance < 0)
        {
            throw new Exception\LogicException('Customer Balance: Tried to set negative balance - ' . $balance);
        }

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function setDailyUsage(int $amount)
    {
        $this->setAttribute(self::DAILY_USAGE, $amount);
    }

    public function setWeeklyUsage(int $amount)
    {
        $this->setAttribute(self::WEEKLY_USAGE, $amount);
    }

    public function setMonthlyUsage(int $amount)
    {
        $this->setAttribute(self::MONTHLY_USAGE, $amount);
    }

    public function setLastLoadedAt($timestamp)
    {
        $this->setAttribute(self::LAST_LOADED_AT, $timestamp);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getMaxbalance()
    {
        return $this->getAttribute(self::MAX_BALANCE);
    }

    public function getDailyUsage()
    {
        return $this->getAttribute(self::DAILY_USAGE);
    }

    public function getWeeklyUsage()
    {
        return $this->getAttribute(self::WEEKLY_USAGE);
    }

    public function getMonthlyUsage()
    {
        return $this->getAttribute(self::MONTHLY_USAGE);
    }

    public function getLastLoadedAt()
    {
        return $this->getAttribute(self::LAST_LOADED_AT);
    }

    // -------------------- Helpers ----------------------------

    public function addBalance(int $amount)
    {
        $balance = $this->getBalance() + $amount;

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function deductBalance(int $amount)
    {
        $balance = $this->getBalance() - $amount;

        if ($balance < 0)
        {
            throw new Exception\LogicException('Customer Balance: Balance went negative on debit - ' . $balance);
        }

        $this->setAttribute(self::BALANCE, $balance);
    }

    // -------------------- End Helpers -------------------------

    public function getMaxBalanceAttribute()
    {
        $balance = $this->attributes[self::MAX_BALANCE];

        if ($balance === null)
        {
            $balance = self::MAX_BALANCE_DEFAULT;
        }

        return (int) $balance;
    }

    protected function setPublicMonthlyUsageAttribute(array & $attributes)
    {
        $monthlyUsage = $this->getMonthlyUsage();

        $lastTxnTime = $this->getLastLoadedAt();

        $lastTxnTime = Carbon::createFromTimestamp($lastTxnTime, Timezone::IST);

        $now = Carbon::now(Timezone::IST);

        if ($lastTxnTime->month !== $now->month)
        {
            $monthlyUsage = 0;
        }

        $attributes[self::MONTHLY_USAGE] = $monthlyUsage;
    }

    protected function setPublicCustomerIdAttribute(array & $attributes)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $attributes[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
    }
}
