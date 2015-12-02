<?php

namespace Models\Merchant\Webhook;

use EE\Exception;
use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const URL               = 'url';
    const EVENTS            = 'events';
    const FAILURE_COUNT     = 'failure_count';
    const ACTIVE            = 'active';

    protected $entity       = 'webhook';

    protected $table        = \Constants\Table::WEBHOOK;

    protected $genereateIdOnCreate = true;

    protected $defaults = array(
        self::ACTIVE        => true,
        self::FAILURE_COUNT => 0,
    );

    protected $fillable = array(
        self::URL,
        self::EVENTS,
    );

    protected $public = array(
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
    );

    public function merchant()
    {
        return $this->belongsTo(\Models\Merchant\Entity::class);
    }

    public function getUrl()
    {
        return $this->getAttribute(self::URL);
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getFailureCount()
    {
        return (int) $this->getAttribute(self::FAILURE_COUNT);
    }

    public function setEventsAttribute(array $events)
    {
        $int = 0;

        foreach ($events as $event => $value)
        {
            if ($value !== '1')
            {
                continue;
            }

            $int = $int ^ Name::getBitValue($event);
        }

        $this->attributes[self::EVENTS] = $int;
    }

    public function getEventsAttribute()
    {
        $events = $this->attributes[self::EVENTS];

        $events = Name::getEnabledEvents($events);

        $names = Name::getAllEventNames();

        $eventsArray = [];

        foreach ($names as $name)
        {
            $eventsArray[$name] = in_array($name, $events);
        }

        return $eventsArray;
    }

    public function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    public function getFailureCountAttribute()
    {
        return (int) $this->attributes[self::FAILURE_COUNT];
    }
}