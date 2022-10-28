<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits\HardDeletes;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;

class Entity extends Base\PublicEntity
{
    use HardDeletes;
    use TransactionalModelEvents\TransactionalAwareEvents;

    const TYPE     = 'type';
    const EMAIL    = 'email';
    const VERIFIED = 'verified';
    const PHONE    = 'phone';
    const POLICY   = 'policy';
    const URL      = 'url';

    protected $entity = 'merchant_email';

    protected $fillable = [
        self::TYPE,
        self::EMAIL,
        self::PHONE,
        self::POLICY,
        self::URL,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::EMAIL,
        self::PHONE,
        self::POLICY,
        self::URL,
    ];

    protected $publicCustomer = [
        self::EMAIL,
        self::PHONE,
        self::URL,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::VERIFIED => 0,
        self::EMAIL => null
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

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }
}
