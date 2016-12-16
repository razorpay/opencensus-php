<?php

namespace RZP\Models\Offer\Coupon;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const OFFER_ID   = 'offer_id';
    const CODE       = 'code';
    const EXPIRES_AT = 'expires_at';

    protected $entity = 'feature';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::OFFER_ID,
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

    public function offer()
    {
        return $this->belongsTo('RZP\Models\Offer\Entity');
    }
}
