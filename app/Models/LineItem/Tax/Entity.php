<?php

namespace RZP\Models\LineItem\Tax;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Tax as TaxModel;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const LINE_ITEM_ID            = 'line_item_id';
    const TAX_ID                  = 'tax_id';
    const NAME                    = 'name';
    const RATE                    = 'rate';
    const RATE_TYPE               = 'rate_type';
    const GROUP_ID                = 'group_id';
    const GROUP_NAME              = 'group_name';
    const TAX_AMOUNT              = 'tax_amount';

    protected static $sign        = 'lit';

    protected $entity             = 'line_item_tax';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::GROUP_ID   => null,
        self::GROUP_NAME => null,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::LINE_ITEM_ID,
        self::TAX_ID,
        self::NAME,
        self::RATE,
        self::RATE_TYPE,
        self::GROUP_ID,
        self::GROUP_NAME,
        self::TAX_AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::TAX_ID,
        self::NAME,
        self::RATE,
        self::RATE_TYPE,
        self::GROUP_ID,
        self::GROUP_NAME,
        self::TAX_AMOUNT,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::NAME,
        self::RATE,
        self::RATE_TYPE,
        self::GROUP_NAME,
        self::TAX_AMOUNT,
    ];

    protected $casts = [
        self::RATE       => 'int',
        self::TAX_AMOUNT => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::TAX_ID,
        self::GROUP_ID,
    ];

    protected $ignoredRelations = [
        'lineItem',
    ];

    // Getters

    public function getTaxAmount(): int
    {
        return $this->getAttribute(self::TAX_AMOUNT);
    }

    public function getRateType(): string
    {
        return $this->getAttribute(self::RATE_TYPE);
    }

    public function getRate(): int
    {
        return $this->getAttribute(self::RATE);
    }

    public function getRatePercentValue(): float
    {
        return $this->getRate() * TaxModel\Entity::PERCENT_MULTIPLIER;
    }

    // Public setters


    protected function setPublicTaxIdAttribute(array & $array)
    {
        $taxId = $this->getAttribute(self::TAX_ID);

        $array[self::TAX_ID] = TaxModel\Entity::getSignedId($taxId);
    }

    protected function setPublicGroupIdAttribute(array & $array)
    {
        $groupId = $this->getAttribute(self::GROUP_ID);

        $array[self::GROUP_ID] = TaxModel\Group\Entity::getSignedIdOrNull($groupId);
    }

    // Relations

    public function lineItem()
    {
        return $this->belongsTo('RZP\Models\LineItem\Tax\Entity');
    }

    public function tax()
    {
        return $this->belongsTo('RZP\Models\Tax\Entity');
    }

    public function taxGroup()
    {
        return $this->belongsTo('RZP\Models\Tax\Group\Entity', Entity::GROUP_ID);
    }
}
