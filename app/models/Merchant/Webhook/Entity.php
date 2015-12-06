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

    const MAX_FAILURE_COUNT = 3;

    protected $table        = \Constants\Table::WEBHOOK;

    protected $genereateIdOnCreate = true;

    protected $defaults = array(
        self::ACTIVE        => true,
        self::FAILURE_COUNT => 0,
    );

    protected $fillable = array(
        self::URL,
        self::ACTIVE,
        self::EVENTS,
    );

    protected $visible = array(
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::MERCHANT_ID,
        self::FAILURE_COUNT,
    );

    protected $public = array(
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
    );

    public function edit(array $input = array(), $operation = 'edit')
    {
        parent::edit($input, $operation);

        if ((isset($input[self::ACTIVE])) and
            ($input[self::ACTIVE] === '1'))
        {
            $this->setAttribute(self::FAILURE_COUNT, 0);
        }
    }

    public function merchant()
    {
        return $this->belongsTo(\Models\Merchant\Entity::class);
    }

    public function incrementFailureCount()
    {
        $count = $this->getFailureCount();
        $count++;
        $this->setFailureCountAttribute($count);

        assert($count <= self::MAX_FAILURE_COUNT);

        if ($count === MAX_FAILURE_COUNT)
        {
            $this->deactivate();
        }
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
        return $this->getAttribute(self::FAILURE_COUNT);
    }

    public function setEventsAttribute($events)
    {
        $hex = 0;

        if (isset($this->attributes[self::EVENTS]))
        {
            $hex = $this->attributes[self::EVENTS];
        }

        $this->attributes[self::EVENTS] = Name::getHexValue($events, $hex);
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

    protected function getActiveAttribute()
    {
        return (bool) $this->attributes[self::ACTIVE];
    }

    protected function getFailureCountAttribute()
    {
        return (int) $this->attributes[self::FAILURE_COUNT];
    }

    protected function setFailureCountAttribute($count)
    {
        assert ($count <= self::MAX_FAILURE_COUNT);

        $this->attributes[self::FAILURE_COUNT] = $count;
    }

    protected function deactivate()
    {
        $this->setAttribute(self::ACTIVE, 0);
    }
}