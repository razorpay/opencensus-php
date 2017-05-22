<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CODE        = 'code';
    const START_DATE  = 'start_date';
    const END_DATE    = 'end_date';

    protected $entity = 'coupon';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::CODE,
        self::START_DATE,
        self::END_DATE,
    ];

    protected $public = [
        self::ID,
        self::CODE,
        self::START_DATE,
        self::END_DATE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $visible = [
        self::ID,
        self::CODE,
        self::START_DATE,
        self::END_DATE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::START_DATE => null,
        self::END_DATE   => null,
    ];

    protected $casts = [
        self::START_DATE => 'int',
        self::END_DATE   => 'int',
    ];

    /**
     * Creates a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'entity' key
     */
    public function source()
    {
        return $this->morphTo('source', self::ENTITY_TYPE, self::ENTITY_ID);
    }
}
