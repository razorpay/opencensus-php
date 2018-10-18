<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const TYPE       = 'type';
    const EMAIL      = 'email';
    const VERIFIED   = 'verified';

    protected $entity = 'merchant_email';

    protected $fillable = [
        self::TYPE,
        self::EMAIL,
    ];

    protected $visible = [
        self::ID,
        self::TYPE,
        self::EMAIL,
        self::VERIFIED,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::MERCHANT_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::VERIFIED => 0,
    ];

    protected $generateIdOnCreate = false;

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
