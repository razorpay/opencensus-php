<?php

namespace RZP\Models\Transfer;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const FROM              = 'from';
    const FROM_ID           = 'from_id';
    const TO                = 'to';
    const TO_ID             = 'to_id';
    const AMOUNT            = 'amount';
    const TRANSACTION_ID    = 'transaction_id';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::FROM,
        self::FROM_ID,
        self::TO,
        self::TO_ID,
        self::AMOUNT,
        self::TRANSACTION_ID
    ];

    protected $visible = [
        self::ID,
        self::FROM,
        self::FROM_ID,
        self::TO,
        self::TO_ID,
        self::AMOUNT,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::FROM,
        self::FROM_ID,
        self::TO,
        self::TO_ID,
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

    // -------------------- End Relations -----------------------

}
