<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id'; //required?
    const CUSTOMER_ID       = 'customer_id';
    const NAME              = 'name';
    const BALANCE           = 'balance';
    const MIN_BALANCE       = 'min_balance';
    const MAX_BALANCE       = 'max_balance';

    protected static $sign = 'wallet';

    protected $entity = 'wallet';

    protected $generateIdOnCreate = true;

    protected $fillable = [
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

}
