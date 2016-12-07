<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const STATUS            = 'status';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CREDIT            = 'credit';
    const DEBIT             = 'debit';
    const BALANCE           = 'balance';
    const DESCRIPTION       = 'description';

    protected static $sign = 'ctxn';

    protected $entity = 'customer_transactions';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT
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

    public function getBalance()
    {
        return (int) $this->getAttribute(self::BALANCE);
    }

    public function getDebit()
    {
        return (int) $this->getAttribute(self::DEBIT);
    }

    public function getCredit()
    {
        return (int) $this->getAttribute(self::CREDIT);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function setBalance($amount)
    {
        $this->setAttribute(self::BALANCE, $amount);
    }

    public function setDebit($amount)
    {
        $this->setAttribute(self::DEBIT, $amount);
    }

    public function setCredit($amount)
    {
        $this->setAttribute(self::CREDIT, $amount);
    }

}
