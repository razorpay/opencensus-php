<?php

namespace Models\Event;

class Entity extends Base\PublicEntity
{
    const EVENT                 = 'event';
    const MERCHANT_ID           = 'merchant_id';
    const CONTAINS              = 'contains';
    const PAYLOAD               = 'payload';
    const CREATED_AT            = 'created_at';

    const URL                   = 'url';

    protected $entity           = 'event';

    protected $fillable = array(
        self::EVENT,
        self::URL,
        self::MERCHANT_ID,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT);

    protected $visible = array(
        self::URL,
        self::EVENT,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT);

    protected $public = array(
        self::EVENT,
        self::MERCHANT_ID,
        self::CONTAINS,
        self::PAYLOAD,
        self::CREATED_AT);
}