<?php

namespace RZP\Models\Item;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ACTIVE                = 'active';
    const NAME                  = 'name';
    const MERCHANT_ID           = 'merchant_id';
    const DESCRIPTION           = 'description';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const DELETED_AT            = 'deleted_at';

    //
    // We use this property to pluck all item realated fields from request
    // payload of line items operations. Yes, that payload contains item related
    // fields at the same level.
    //
    public static $allFields    = [
        self::ACTIVE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected static $sign      = 'item';

    protected $entity           = 'item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::ACTIVE            => 1,
        self::DESCRIPTION       => null,
        self::CURRENCY          => 'INR',
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::ACTIVE,
        self::MERCHANT_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ACTIVE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $fillable = [
        self::ACTIVE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
    ];

    protected $casts = [
        self::ACTIVE    => 'bool',
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

    public function isActive()
    {
        return ($this->getAttribute(self::ACTIVE) === true);
    }

    public function isNotActive()
    {
        return ($this->isActive() === false);
    }

    // -------------------------- Getters Ends --------------------------

    // -------------------- Relations ---------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function lineItems()
    {
        return $this->hasMany('RZP\Models\LineItem\Entity');
    }

    // -------------------- End Relations -----------------------
}
