<?php

namespace RZP\Models\Coupon;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID = 'merchant_id';
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CODE        = 'code';
    const START_AT    = 'start_at';
    const END_AT      = 'end_at';
    const MAX_COUNT   = 'max_count';
    const USED_COUNT  = 'used_count';
    const DELETED_AT  = 'deleted_at';

    const COUPON_CODE = 'coupon_code';
    const ENTITY_TYPE_PROMOTION = 'promotion';
    const IS_INTERNAL = 'is_internal';
    const ALERTS = 'alerts';

    protected $entity = 'coupon';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::CODE,
        self::START_AT,
        self::END_AT,
        self::MAX_COUNT,
        self::IS_INTERNAL,
        self::ALERTS
    ];

    protected $visible = [
        self::ID,
        self::CODE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::MAX_COUNT,
        self::START_AT,
        self::END_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IS_INTERNAL,
        self::ALERTS
    ];

    protected $defaults = [
        self::USED_COUNT => 0,
        self::IS_INTERNAL =>false,
    ];

    protected $casts = [
        self::START_AT   => 'int',
        self::END_AT     => 'int',
        self::MAX_COUNT  => 'int',
        self::USED_COUNT => 'int',
        self::IS_INTERNAL => 'bool',
        self::ALERTS => 'array'
    ];

    public static function getDefaultAlerts()
    {
        return [
            Constants::EMAIL          => [],
            Constants::SLACK          => []
        ];
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    /**
     * Defines a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'source' key
     */
    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function getMaxCount()
    {
        return $this->getAttribute(self::MAX_COUNT);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function getStartAt()
    {
        return $this->getAttribute(self::START_AT);
    }

    public function getEndAt()
    {
        return $this->getAttribute(self::END_AT);
    }

    public function getCode()
    {
        return $this->getAttribute(self::CODE);
    }

    public function incrementUsedCount()
    {
        $usedCount = $this->getUsedCount();

        $usedCount = $usedCount + 1;

        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function isInternal()
    {
        return $this->getAttribute(self::IS_INTERNAL);
    }
}
