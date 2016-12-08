<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const CUSTOMER_ID   = 'customer_id';
    const MERCHANT_ID   = 'merchant_id';
    const BALANCE       = 'balance';
    const DAILY_USAGE   = 'daily_usage';
    const WEEKLY_USAGE  = 'weekly_usage';
    const MONTHLY_USAGE = 'monthly_usage';
    const MAX_BALANCE   = 'max_balance';

    protected static $sign = 'cust';

    protected $entity = 'customer_balance';

    public $incrementing = true;

    protected $fillable = [
        self::BALANCE,
        self::DAILY_USAGE,
        self::WEEKLY_USAGE,
        self::MONTHLY_USAGE,
        self::MAX_BALANCE
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
        self::UPDATED_AT
    ];

    protected $public = [
        self::BALANCE,
        self::MONTHLY_USAGE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::BALANCE       => 0,
        self::MAX_BALANCE   => 2000000,
        self::DAILY_USAGE   => 0,
        self::WEEKLY_USAGE  => 0,
        self::MONTHLY_USAGE => 0,
    ];

    protected $publicSetters = [
        self::CUSTOMER_ID,
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

    public function setBalance($balance)
    {
        assert($balance >= 0);

        $this->setAttribute(self::BALANCE, $balance);
    }


    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getBalance()
    {
        return (int) $this->getAttribute(self::BALANCE);
    }

    public function getMaxbalance()
    {
        return (int) $this->getAttribute(self::MAX_BALANCE);
    }

    public function getDailyUsage()
    {
        return (int) $this->getAttribute(self::DAILY_USAGE);
    }

    public function getWeeklyUsage()
    {
        return (int) $this->getAttribute(self::WEEKLY_USAGE);
    }

    public function getMonthlyUsage()
    {
        return (int) $this->getAttribute(self::MONTHLY_USAGE);
    }

    // -------------------- Helpers ----------------------------

    protected function addBalance(int $amount)
    {
        $this->checkNumeric($amount);

        $balance = $this->getBalance() + $amount;

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function deductBalance($amount) //@todo: Refactor, change to protected
    {
        $this->checkNumeric($amount);

        $balance = $this->getBalance() - $amount;

        assert($balance >= 0);

        $this->setAttribute(self::BALANCE, $balance);
    }

    /**
     * Only this method should be public
     * for updating balance.
     * We need to check for balance going negative
     * whenever we update balance
     *
     * @param  \RZP\Models\Transaction\Entity $txn
     * @throws Exception\LogicException
     */
    public function updateBalance($txn)
    {
        $amount = $txn->getNetAmount();

        // Negating the amount here because customer balance txn is a debit wrt merchants
        // NetAmount for debits will be negative
        $this->addBalance(-1 * $amount);

        if ($this->getBalance() < 0)
        {
            $data = [
                'balance' => $this->toArray(),
                'transaction' => $txn->toArray(),
                'amount' => $amount
            ];

            throw new Exception\LogicException(
                'Something very wrong is happening! Balance is going negative',
                null,
                $data);
        }
    }

    protected function checkNumeric($arg)
    {
        if (is_int($arg) === false)
        {
            throw new Exception\InvalidArgumentException('
                Unsigned integer required. Supplied: ' . $arg);
        }
    }

    // -------------------- End Helpers -------------------------


    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
    }
}
