<?php

namespace RZP\Models\Tax;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    /**
     * We keep tax rate as multiple of 10000 in case it is percentage type.
     * So to return the actual percent value we multiply the value by 0.000001.
     */
    const PERCENT_MULTIPLIER = 0.000001;

    use SoftDeletes;

    // Table attributes

    const MERCHANT_ID = 'merchant_id';
    const NAME        = 'name';

    /**
     * Rate type: Either of percentage, flat.
     */
    const RATE_TYPE   = 'rate_type';

    /**
     * Rate: The rate value. Must be integer.
     * In case of flat type, it's integer value in paisa, otherwise percentage
     * amount multiplied by 100 (so between 0 - 10000).
     */
    const RATE        = 'rate';

    // Additional output keys
    const GST_TAX_SLABS_V2  = 'gst_tax_slabs_v2';
    const GST_TAX_ID_MAP_V2 = 'gst_tax_id_map_v2';

    protected static $sign = 'tax';

    protected $entity = 'tax';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::RATE_TYPE => RateType::PERCENTAGE,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::MERCHANT_ID,
        self::NAME,
        self::RATE_TYPE,
        self::RATE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::RATE_TYPE,
        self::RATE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::NAME,
        self::RATE_TYPE,
        self::RATE,
    ];

    protected $casts = [
        self::RATE => 'int',
    ];

    // Getters

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getRateType()
    {
        return $this->getAttribute(self::RATE_TYPE);
    }

    public function getRate(): int
    {
        return $this->getAttribute(self::RATE);
    }

    public function getRatePercentValue(): float
    {
        return $this->getRate() * self::PERCENT_MULTIPLIER;
    }

    // Relations

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function items()
    {
        return $this->hasMany('RZP\Models\Item\Entity');
    }
}
