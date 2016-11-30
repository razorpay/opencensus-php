<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
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

    public $incrementing = true;

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

    protected $public = [
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
