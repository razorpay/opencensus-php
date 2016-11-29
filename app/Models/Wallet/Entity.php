<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const CUSTOMER_ID       = 'customer_id';
    const MERCHANT_ID       = 'merchant_id';
    const NAME              = 'name';
    const BALANCE           = 'balance';
    const MIN_BALANCE       = 'min_balance';
    const MAX_BALANCE       = 'max_balance';

    protected static $sign = 'cust';

    protected $entity = 'wallets';

    public $incrementing = true;

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


    // -------------------- Helpers ----------------------------

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

    // -------------------- End Helpers -------------------------


    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
    }


}
