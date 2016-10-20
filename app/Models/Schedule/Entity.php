<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const NAME        = 'name';
    const OWNER_ID    = 'owner_id';
    const TYPE        = 'type';
    const PERIOD      = 'period';
    const INTERVAL    = 'interval';
    const ANCHOR      = 'anchor';
    const DELAY       = 'delay';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::MERCHANT_ID,
        self::TYPE,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::DELAY,
    );

    protected $table = \RZP\Constants\Table::SCHEDULE;

    protected $entity = 'schedule';

    protected $generateIdOnCreate = true;

    // ----------------------- Getters ---------------------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getOwnerId()
    {
        return $this->getAttribute(self::OWNER_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getPeriod()
    {
        return $this->getAttribute(self::PERIOD);
    }

    public function getInterval()
    {
        return $this->getAttribute(self::INTERVAL);
    }

    public function getAnchor()
    {
        return $this->getAttribute(self::ANCHOR);
    }

    public function getDelay()
    {
        return $this->getAttribute(self::DELAY);
    }

}