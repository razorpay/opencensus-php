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
    const TYPE                  = 'type';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'item';

    protected $entity           = 'item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::ACTIVE        => 1,
        self::DESCRIPTION   => null,
        self::TYPE          => Type::INVOICE,
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
        self::TYPE,
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
        self::TYPE,
    ];

    protected $fillable = [
        self::ACTIVE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::TYPE,
    ];

    protected $casts = [
        self::ACTIVE    => 'bool',
        self::AMOUNT    => 'int',
    ];

    // -------------------------- Getters --------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isActive()
    {
        return ($this->getAttribute(self::ACTIVE) === true);
    }

    public function isNotActive()
    {
        return ($this->isActive() === false);
    }

    // -------------------------- End Getters --------------------------

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
