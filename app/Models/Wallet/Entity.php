<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const CUSTOMER_ID       = 'customer_id';
    const NAME              = 'name';
    const BALANCE           = 'balance';
    const MIN_BALANCE       = 'min_balance';
    const MAX_BALANCE       = 'max_balance';

    protected static $sign = 'cust';

    protected $entity = 'wallets';

    protected $primaryKey = self::CUSTOMER_ID;

    protected $fillable = [
        self::CUSTOMER_ID,
        self::NAME,
        self::BALANCE,
        self::MIN_BALANCE,
        self::MAX_BALANCE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $visible = [
        self::CUSTOMER_ID,
        self::NAME,
        self::BALANCE,
        self::MIN_BALANCE,
        self::MAX_BALANCE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::CUSTOMER_ID,
        self::NAME,
        self::BALANCE,
        self::MIN_BALANCE,
        self::MAX_BALANCE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    // -------------------- Relations ---------------------------

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    // -------------------- End Relations -----------------------

    public function setBalance($balance)
    {
        assert($balance >= 0);

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getMinBalance()
    {
        return $this->getAttribute(self::MIN_BALANCE);
    }

    public function getMaxbalance()
    {
        return $this->getAttribute(self::MAX_BALANCE);
    }


    // Helpers
    public function addBalance($amount)
    {
        $balance = $this->getBalance() + $amount;

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function deductBalance($amount)
    {
        $balance = $this->getBalance() - $amount;

        assert($balance >= 0);

        $this->setAttribute(self::BALANCE, $balance);
    }

}
