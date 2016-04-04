<?php

namespace Models\Merchant\Webhook;

use Constants\Table;
use EE\Exception;
use Models\Base;
use Crypt;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const URL               = 'url';
    const EVENTS            = 'events';
    const FAILURE_COUNT     = 'failure_count';
    const ACTIVE            = 'active';
    const CREATED_AT        = 'created_at';
    const SECRET            = 'secret';
    
    protected $entity       = 'webhook';

    const MAX_FAILURE_COUNT = 3;

    protected $table        = Table::WEBHOOK;

    protected $generateIdOnCreate = true;

    protected $defaults = array(
        self::ACTIVE        => true,
        self::FAILURE_COUNT => 0,
    );

    protected $fillable = array(
        self::URL,
        self::ACTIVE,
        self::EVENTS,
        self::SECRET
    );

    protected $visible = array(
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::MERCHANT_ID,
        self::FAILURE_COUNT,
        self::CREATED_AT,
        self::SECRET
    );

    protected $public = array(
        self::ID,
        self::URL,
        self::EVENTS,
        self::ACTIVE,
        self::CREATED_AT,
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

    public function bumpFailureCount()
    {
        $count = $this->getFailureCount() + 1;

        $this->setFailureCountAttribute($count);
    }

    public function getUrl()
    {
        return $this->getAttribute(self::URL);
    }

    public function getSecret()
    {
        $encryptedSecret = $this->getAttribute(self::SECRET);
        if(!empty($encryptedSecret))
        {
            return Crypt::decrypt($encryptedSecret);
        }
        return NULL;
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

        $this->attributes[self::EVENTS] = Event::getHexValue($events, $hex);
    }

    public function getEventsAttribute()
    {
        $events = $this->attributes[self::EVENTS];

        $events = Event::getEnabledEvents($events);

        $names = Event::getAllEventNames();

        $eventsArray = [];

        foreach ($names as $name)
        {
            $eventsArray[$name] = in_array($name, $events);
        }

        return $eventsArray;
    }

    public function isEventEnabled($event)
    {
        $hex = $this->getEventsHexValue();

        return Event::isEventEnabled($hex, $event);
    }

    public function resetFailureCount()
    {
        $this->setFailureCountAttribute(0);

        $this->activate();
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

        if ($count === self::MAX_FAILURE_COUNT)
        {
            $this->deactivate();
        }
    }

    protected function getEventsHexValue()
    {
        return $this->attributes[self::EVENTS];
    }

    protected function deactivate()
    {
        $this->setAttribute(self::ACTIVE, 0);
    }

    protected function activate()
    {
        $this->setAttribute(self::ACTIVE, 1);
    }
}
