<?php

namespace RZP\Models\LineItem;

use App;
use RZP\Models\Base;
use RZP\Models\Item;

class Entity extends Base\PublicEntity
{
    const ENTITY_ID        = 'entity_id';
    const ENTITY_TYPE      = 'entity_type';
    const ITEM_ID          = 'item_id';
    const QUANTITY         = 'quantity';

    const ITEM             = 'item';

    protected static $sign = 'li';

    protected $entity      = 'line_item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::QUANTITY          => 1,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::QUANTITY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ITEM_ID,
        self::ITEM,
    ];

    protected $public = [
        self::ID,
        self::QUANTITY,
        self::ITEM_ID,
        self::ITEM,
    ];

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::QUANTITY,
    ];

    protected $casts = [
        self::QUANTITY  => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
        self::ITEM,
    ];

    // -------------------------- Getters --------------------------

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    // -------------------------- Getters Ends --------------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedId($this->getAttribute(self::ITEM_ID));
    }

    protected function setPublicItemAttribute(array & $array)
    {
        $array[self::ITEM] = $this->item->toArrayPublic();
    }

    // -------------------- Relations ---------------------------

    public function entity()
    {
        return $this->morphTo();
    }

    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }

    // -------------------- End Relations -----------------------
}
