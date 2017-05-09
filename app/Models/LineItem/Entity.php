<?php

namespace RZP\Models\LineItem;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Plan\Subscription\Addon;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID        = 'entity_id';
    const ENTITY_TYPE      = 'entity_type';
    const MERCHANT_ID      = 'merchant_id';
    const ITEM_ID          = 'item_id';
    const REF_ID           = 'ref_id';
    const REF_TYPE         = 'ref_type';
    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const AMOUNT           = 'amount';
    const TOTAL_AMOUNT     = 'total_amount';
    const TAX_AMOUNT       = 'tax_amount';
    const NET_AMOUNT       = 'net_amount';
    const CURRENCY         = 'currency';
    const TAX_INCLUSIVE    = 'tax_inclusive';
    const UNIT             = 'unit';
    const QUANTITY         = 'quantity';
    const DELETED_AT       = 'deleted_at';

    // Input keys

    const LINE_ITEMS       = 'line_items';
    const IDS              = 'ids';

    // This is used to send the whole ref object
    // as part of the line item itself.
    const REF              = 'ref';
    const TAX_ID           = 'tax_id';
    const TAX_GROUP_ID     = 'tax_group_id';

    // Output keys

    const TAXES            = 'taxes';

    protected static $sign = 'li';

    protected $entity      = 'line_item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::QUANTITY    => 1,
        self::DESCRIPTION => null,
        self::REF_ID      => null,
        self::REF_TYPE    => null,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::QUANTITY,
        self::ITEM_ID,
        self::REF_ID,
        self::REF_TYPE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::TOTAL_AMOUNT,
        self::TAX_AMOUNT,
        self::NET_AMOUNT,
        self::CURRENCY,
        self::TAX_INCLUSIVE,
        self::UNIT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        // Uncomment later when required
        // self::ITEM_ID,
        // self::REF_ID,
        // self::REF_TYPE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::TOTAL_AMOUNT,
        self::TAX_AMOUNT,
        self::NET_AMOUNT,
        self::CURRENCY,
        self::TAX_INCLUSIVE,
        self::UNIT,
        self::QUANTITY,
        self::TAXES,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::TAX_INCLUSIVE,
        self::UNIT,
        self::QUANTITY,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::TOTAL_AMOUNT  => 'int',
        self::TAX_AMOUNT    => 'int',
        self::NET_AMOUNT    => 'int',
        self::TAX_INCLUSIVE => 'bool',
        self::QUANTITY      => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ITEM_ID,
        self::REF_ID,
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
        self::UNIT,
        self::TAX_INCLUSIVE,
        // self::TAX_ID,
        // self::TAX_GROUP_ID,
    ];

    // -------------------------- Getters ----------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getTotalAmount()
    {
        return $this->getAttribute(self::TOTAL_AMOUNT);
    }

    public function getTaxAmount()
    {
        return $this->getAttribute(self::TAX_AMOUNT);
    }

    public function getNetAmount()
    {
        return $this->getAttribute(self::NET_AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    // -------------------------- Getters Ends -----------------------

    // Setters

    public function setTotalAmount($totalAmount)
    {
        $this->setAttribute(self::TOTAL_AMOUNT, $totalAmount);
    }

    public function setTaxAmount($taxAmount)
    {
        $this->setAttribute(self::TAX_AMOUNT, $taxAmount);
    }

    public function setNetAmount($netAmount)
    {
        $this->setAttribute(self::NET_AMOUNT, $netAmount);
    }

    // -------------------------- Public Setters ---------------------

    protected function setPublicItemIdAttribute(array & $array)
    {
        $array[self::ITEM_ID] = Item\Entity::getSignedIdOrNull($this->getAttribute(self::ITEM_ID));
    }

    public function setPublicRefIdAttribute(array & $array)
    {
        $refType = $array[self::REF_TYPE];

        if ($refType === null)
        {
            return;
        }

        $entity = Constants\Entity::getEntityClass($refType);

        $array[self::REF_ID] = $entity::getSignedId($array[self::REF_ID]);
    }

    // -------------------------- Public Setters Ends ----------------

    // -------------------- Relations --------------------------------

    public function entity()
    {
        return $this->morphTo();
    }

    public function ref()
    {
        return $this->morphTo();
    }

    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }

    public function addon()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Addon\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function taxes()
    {
        return $this->hasMany('RZP\Models\LineItem\Tax\Entity');
    }

    // -------------------- End Relations ----------------------------
}
