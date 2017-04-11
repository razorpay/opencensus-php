<?php

namespace RZP\Models\LineItem;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\AddOn;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID        = 'entity_id';
    const ENTITY_TYPE      = 'entity_type';
    const MERCHANT_ID      = 'merchant_id';
    const ITEM_ID          = 'item_id';
    const ADD_ON_ID        = 'add_on_id';
    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const QUANTITY         = 'quantity';
    const DELETED_AT       = 'deleted_at';

    //
    // Input keys
    //

    const LINE_ITEMS       = 'line_items';
    const IDS              = 'ids';

    protected static $sign = 'li';

    protected $entity      = 'line_item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::QUANTITY    => 1,
        self::DESCRIPTION => null,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::QUANTITY,
        self::ITEM_ID,
        self::ADD_ON_ID,
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
        // Uncomment later when required
        // self::ITEM_ID,
        // self::ADD_ON_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::QUANTITY,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::QUANTITY,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
        self::QUANTITY  => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
        self::ADD_ON_ID,
    ];

    //
    // Fields which can be populated from item template, if item_id is provided
    // in input.
    //
    public static $itemFields = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
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

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    // -------------------------- Getters Ends --------------------------

    // -------------------------- Public Setters --------------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedId($this->getAttribute(self::ITEM_ID));
    }

    protected function setPublicAddOnIdAttribute(array & $array)
    {
        $array[self::ADD_ON_ID] = AddOn\Entity::getSignedId($this->getAttribute(self::ADD_ON_ID));
    }

    // -------------------------- Public Setters Ends --------------------------

    // -------------------- Relations ---------------------------

    public function entity()
    {
        return $this->morphTo();
    }

    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }

    public function addOn()
    {
        return $this->belongsTo('RZP\Models\AddOn\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // -------------------- End Relations -----------------------
}
