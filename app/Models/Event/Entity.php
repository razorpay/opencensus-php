<?php

namespace RZP\Models\Event;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const EVENT                 = 'event';
    const MERCHANT_ID           = 'merchant_id';
    const CONTAINS              = 'contains';
    const PAYLOAD               = 'payload';
    const CREATED_AT            = 'created_at';
    const ACCOUNT_ID            = 'account_id';

    protected $entity           = 'event';

    protected $fillable = [
        self::EVENT,
        self::ACCOUNT_ID,
        self::MERCHANT_ID,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT
    ];

    protected $visible = [
        self::EVENT,
        self::ACCOUNT_ID,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT
    ];

    protected $public = [
        self::ENTITY,
        self::ACCOUNT_ID,
        self::EVENT,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function setPayload($payload)
    {
        $this->setAttribute(self::PAYLOAD, $payload);
    }
}
