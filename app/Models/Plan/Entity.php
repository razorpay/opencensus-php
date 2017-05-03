<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Item;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const PERIOD            = 'period';
    const INTERVAL          = 'interval';
    const NAME              = 'name';
    const NOTES             = 'notes';
    const MERCHANT_ID       = 'merchant_id';
    const ITEM_ID           = 'item_id';

    // Input Keys

    // const FREQUENCY         = 'frequency';

    protected static $sign = 'plan';

    protected $entity = 'plan';

    protected $table = Table::PLAN;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES => [],
    ];

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::INTERVAL,
        self::PERIOD,
        self::NAME,
        self::NOTES,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::INTERVAL,
        self::PERIOD,
        self::ITEM_ID,
        self::NAME,
        self::NOTES,
        self::CREATED_AT
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
    ];

    // Used for reporting
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

    // --------------------- PUBLIC SETTERS ---------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedIdOrNull($this->getAttribute(self::ITEM_ID));
    }
}
