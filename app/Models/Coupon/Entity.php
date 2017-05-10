<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CODE        = 'code';
    const EXPIRES_AT  = 'expires_at';

    protected $entity = 'coupon';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::CODE,
        self::EXPIRES_AT
    ];

    protected $visible = [
        self::ID,
        self::CODE,
        self::EXPIRES_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    /**
     * Creates a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'entity' key
     */
    public function entity()
    {
        return $this->morphTo();
    }
}
