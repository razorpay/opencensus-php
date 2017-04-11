<?php

namespace RZP\Models\AddOn;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Item;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const SUBSCRIPTION_ID  = 'subscription_id';
    const MERCHANT_ID      = 'merchant_id';
    const ITEM_ID          = 'item_id';
    const INVOICE_ID       = 'invoice_id';
    const DELETED_AT       = 'deleted_at';

    // Input Keys
    const AMOUNT    = 'amount';
    const CURRENCY  = 'currency';
    const NAME      = 'name';

    protected static $sign = 'ao';

    protected $entity = 'add_on';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::SUBSCRIPTION_ID,
        self::ITEM_ID,
        self::INVOICE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ITEM_ID,
        self::CREATED_AT,
    ];

    protected $defaults = [
        self::DELETED_AT => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
        self::INVOICE_ID,
    ];

    // -------------------------- Setters --------------------------

    // -------------------------- Setters Ends --------------------------

    // -------------------------- Public Setters --------------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedId($this->getAttribute(self::ITEM_ID));
    }

    // -------------------------- Public Setters Ends --------------------------

    // -------------------- Relations ---------------------------

    public function subscription()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Entity');
    }

    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    // -------------------- End Relations -----------------------
}
