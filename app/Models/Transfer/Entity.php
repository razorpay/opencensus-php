<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const TO_ID             = 'to_id';
    const TO_TYPE           = 'to_type';
    const SOURCE_ID         = 'source_id';
    const SOURCE_TYPE       = 'source_type';
    const AMOUNT            = 'amount';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::AMOUNT,
        self::SOURCE_ID,
        self::SOURCE_TYPE
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::AMOUNT,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::TO_ID,
        self::TO_TYPE,
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::AMOUNT,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------- End Relations -----------------------

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }
}
