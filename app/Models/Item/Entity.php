<?php

namespace RZP\Models\Item;

use App;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME                  = 'name';
    const MERCHANT_ID           = 'merchant_id';
    const DESCRIPTION           = 'description';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';

    public static $allFields    = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected static $sign      = 'item';

    protected $entity           = 'item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::DESCRIPTION       => null,
        self::CURRENCY          => 'INR',
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::MERCHANT_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
    ];

    // -------------------------- Getters --------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    // -------------------------- Getters Ends --------------------------

    // -------------------- Relations ---------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------- End Relations -----------------------
}
