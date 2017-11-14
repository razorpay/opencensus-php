<?php

namespace RZP\Models\Plan\Subscription\Addon;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Plan\Subscription;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const SUBSCRIPTION_ID  = 'subscription_id';
    const MERCHANT_ID      = 'merchant_id';
    const ITEM_ID          = 'item_id';
    const QUANTITY         = 'quantity';
    const INVOICE_ID       = 'invoice_id';
    const DELETED_AT       = 'deleted_at';

    // Input Keys
    const AMOUNT    = 'amount';
    const CURRENCY  = 'currency';
    const NAME      = 'name';

    const ITEM      = 'item';

    protected static $sign = 'ao';

    protected $entity = 'addon';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::QUANTITY,
        self::SUBSCRIPTION_ID,
        self::ITEM_ID,
        self::ITEM,
        self::MERCHANT_ID,
        self::INVOICE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $fillable = [
        self::QUANTITY,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::ITEM,
        self::QUANTITY,
        self::CREATED_AT,
        self::SUBSCRIPTION_ID,
        self::INVOICE_ID,
    ];

    protected $defaults = [
        self::QUANTITY      => 1,
        self::DELETED_AT    => null,
        self::INVOICE_ID    => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
        self::INVOICE_ID,
        self::SUBSCRIPTION_ID,
    ];

    protected $casts = [
        self::QUANTITY => 'int',
    ];

    // -------------------------- Getters --------------------------

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    public function hasInvoice()
    {
        return $this->isAttributeNotNull(self::INVOICE_ID);
    }

    public function getInvoiceId()
    {
        return $this->getAttribute(self::INVOICE_ID);
    }

    // -------------------------- Getters Ends --------------------------

    // -------------------------- Public Setters --------------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedIdOrNull($this->getAttribute(self::ITEM_ID));
    }

    protected function setPublicInvoiceIdAttribute(array & $array)
    {
        $array[self::INVOICE_ID] = Invoice\Entity::getSignedIdOrNull($this->getAttribute(self::INVOICE_ID));
    }

    protected function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getAttribute(self::SUBSCRIPTION_ID);

        $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedIdOrNull($subscriptionId);
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

    public function lineItems()
    {
        return $this->morphMany('RZP\Models\LineItem\Entity', 'ref');
    }

    // -------------------- End Relations -----------------------

    public function setAssociations(
        Merchant\Entity $merchant,
        Item\Entity $item,
        Subscription\Entity $subscription)
    {
        $this->merchant()->associate($merchant);
        $this->item()->associate($item);
        $this->subscription()->associate($subscription);
    }
}
