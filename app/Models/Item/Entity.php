<?php

namespace RZP\Models\Item;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Tax;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ACTIVE                = 'active';
    const MERCHANT_ID           = 'merchant_id';
    const NAME                  = 'name';
    const DESCRIPTION           = 'description';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const TYPE                  = 'type';

    /**
     * Unit of item, e.g. KG, PCS etc.
     */
    const UNIT                  = 'unit';

    /**
     * Is true if AMOUNT is tax inclusive else false.
     */
    const TAX_INCLUSIVE         = 'tax_inclusive';

    /**
     * One item can have associated either one individual tax
     * or a group of tax via tax_group_id.
     */
    const TAX_ID                = 'tax_id';
    const TAX_GROUP_ID          = 'tax_group_id';

    const DELETED_AT            = 'deleted_at';

    /**
     * These are used when other entities need
     * to create an item and item_id/item is passed
     * in the request input.
     */
    const ITEM_ID               = 'item_id';
    const ITEM                  = 'item';

    protected static $sign      = 'item';

    protected $entity           = 'item';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::ACTIVE        => 1,
        self::DESCRIPTION   => null,
        self::TYPE          => Type::INVOICE,
        self::UNIT          => null,
        self::TAX_INCLUSIVE => false,
        self::TAX_ID        => null,
        self::TAX_GROUP_ID  => null,
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
        self::UNIT,
        self::TAX_INCLUSIVE,
        self::TAX_ID,
        self::TAX_GROUP_ID,
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
        self::UNIT,
        self::TAX_INCLUSIVE,
        self::TAX_ID,
        self::TAX_GROUP_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::ACTIVE,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::TYPE,
        self::UNIT,
        self::TAX_INCLUSIVE,
    ];

    protected $casts = [
        self::ACTIVE        => 'bool',
        self::AMOUNT        => 'int',
        self::TAX_INCLUSIVE => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TAX_ID,
        self::TAX_GROUP_ID,
    ];

    // -------------------------- Getters ----------------------------

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

    public function isTaxInclusive()
    {
        return $this->getAttribute(self::TAX_INCLUSIVE);
    }

    public function getTaxId()
    {
        return $this->getAttribute(self::TAX_ID);
    }

    public function getTaxGroupId()
    {
        return $this->getAttribute(self::TAX_GROUP_ID);
    }

    public function isActive()
    {
        return ($this->getAttribute(self::ACTIVE) === true);
    }

    public function isNotActive()
    {
        return ($this->isActive() === false);
    }

    // -------------------------- End Getters ------------------------

    // Public setters

    public function setPublicTaxIdAttribute(array & $output)
    {
        $taxId = $this->getAttribute(self::TAX_ID);

        $output[self::TAX_ID] = Tax\Entity::getSignedIdOrNull($taxId);
    }

    public function setPublicTaxGroupIdAttribute(array & $output)
    {
        $taxGroupId = $this->getAttribute(self::TAX_GROUP_ID);

        $output[self::TAX_GROUP_ID] = Tax\Group\Entity::getSignedIdOrNull($taxGroupId);
    }

    // -------------------- Relations --------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function lineItems()
    {
        return $this->hasMany('RZP\Models\LineItem\Entity');
    }

    public function addons()
    {
        return $this->hasMany('RZP\Models\Plan\Subscription\Addon\Entity');
    }

    public function tax()
    {
        return $this->belongsTo('RZP\Models\Tax\Entity');
    }

    public function taxGroup()
    {
        return $this->belongsTo('RZP\Models\Tax\Group\Entity');
    }

    // -------------------- End Relations ----------------------------
}
