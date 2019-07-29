<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const TYPE       = 'type';
    const EMAIL      = 'email';
    const VERIFIED   = 'verified';
    const PHONE      = 'phone';
    const POLICY     = 'policy';
    const URL        = 'url';

    protected $entity = 'merchant_email';

    protected $fillable = [
        self::TYPE,
        self::EMAIL,
        self::PHONE,
        self::POLICY,
        self::URL,
    ];

    protected $public = [
        self::EMAIL,
        self::PHONE,
        self::POLICY,
        self::URL,
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }
}
