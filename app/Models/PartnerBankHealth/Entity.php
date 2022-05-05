<?php

namespace RZP\Models\PartnerBankHealth;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    const EVENT_TYPE = 'event_type';
    const VALUE      = 'value';

    const LAST_DOWN_AT = 'last_down_at';
    const LAST_UP_AT   = 'last_up_at';

    protected $generateIdOnCreate = true;

    protected static $sign = 'pbh';

    protected $primaryKey = self::ID;

    protected $entity = EntityConstants::PARTNER_BANK_HEALTH;

    protected static $generators = [
        self::ID,
    ];

    protected $publicSetters = [
        self::ID,
        self::VALUE,
        self::ENTITY,
    ];

    protected $fillable = [
        self::EVENT_TYPE,
        self::VALUE,
    ];

    protected $visible = [
        self::ID,
        self::EVENT_TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::EVENT_TYPE,
        self::VALUE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    public function getEventType()
    {
        return $this->getAttribute(self::EVENT_TYPE);
    }

    public function getValue()
    {
        $value = $this->getAttribute(self::VALUE);

        return json_decode($value, true);
    }

    public function setEventType($key)
    {
        $this->setAttribute(self::EVENT_TYPE, $key);
    }

    public function setValue($value)
    {
        $jsonValue = json_encode($value);

        $this->setAttribute(self::VALUE, $jsonValue);
    }

    public function setPublicValueAttribute(&$attributes)
    {
        $value = $attributes[self::VALUE];

        $attributes[self::VALUE] = json_decode($value, true);
    }
}
