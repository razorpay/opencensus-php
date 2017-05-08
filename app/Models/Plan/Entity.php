<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const PERIOD            = 'period';
    const INTERVAL          = 'interval';
    const NOTES             = 'notes';
    const MERCHANT_ID       = 'merchant_id';
    const ITEM_ID           = 'item_id';

    // Input Keys
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const NAME              = 'name';

    const ITEM              = 'item';

    protected static $sign = 'plan';

    protected $entity = 'plan';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES => [],
    ];

    protected $fillable = [
        self::INTERVAL,
        self::PERIOD,
        self::NOTES,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::INTERVAL,
        self::PERIOD,
        self::ITEM,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $appends = [
        self::ITEM,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM,
    ];

    /**
     * Amount attributes. used for reporting
     *
     * @var array
     */
    protected $amounts = [
        self::AMOUNT,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::INTERVAL          => 'int',
    ];

    // --------------------- GETTERS ---------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getInterval()
    {
        return $this->getAttribute(self::INTERVAL);
    }

    public function getPeriod()
    {
        return $this->getAttribute(self::PERIOD);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- ACCESSORS ---------------------

    /**
     * TODO: Remove this post expand pr is merged. Also remove from $appends.
     *
     * @return Base\PublicCollection
     */
    protected function getItemAttribute()
    {
        $item = $this->item()->get()->first();

        return $item;
    }

    // --------------------- END ACCESSORS ---------------------

    // --------------------- PUBLIC SETTERS ---------------------

    protected function setPublicItemAttribute()
    {
        if (empty($array[self::ITEM]) === true)
        {
            return;
        }

        $array[self::ITEM] = $array[self::ITEM]->toArrayPublic();

        unset($array[self::ITEM][Item\Entity::ID]);
    }

    // --------------------- END PUBLIC SETTERS ---------------------

    // --------------------- RELATIONS ---------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }

    // --------------------- END RELATIONS ---------------------
}
