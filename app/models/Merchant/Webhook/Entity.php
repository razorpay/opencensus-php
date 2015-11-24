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

    protected $entity           = 'webhook';

    protected $table            = \Constants\Table::WEBHOOK;

    public function getUrl()
    {
        return $this->getAttribute(self::URL);
    }

    public function isActive()
    {
        return (bool) $this->getAttribute(self::ACTIVE);
    }

    public function getFailureCount()
    {
        return (int) $this->getAttribute(self::FAILURE_COUNT);
    }

    public function setEventsAttribute(array $events)
    {
        $int = 0;

        foreach ($events as $event)
        {
            $e = str_replace($event, '.', '_');
            $e = strtoupper($e);
            $int = $int xor constant(Name::class.'::'.$e);
        }

        $this->setAttribute(self::EVENTS, $int);
    }

    public function getEventsAttribute()
    {
        $events = $this->attributes[self::EVENTS];

        return Name::getEvents($events);
    }
}