<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Constants\Table;

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
    const NEXT_RUN    = 'next_run';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::TYPE,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::DELAY,
        self::NEXT_RUN,
    );

    protected $table = Table::SCHEDULE;

    protected $entity = 'schedule';

    protected $generateIdOnCreate = true;

    // ----------------------- Associations ----------------------------------------

    public function owner()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Entity', self::OWNER_ID);
    }

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

    public function getNextRun()
    {
        return $this->getAttribute(self::NEXT_RUN);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setNextRun($nextRun)
    {
        return $this->setAttribute(self::NEXT_RUN, $nextRun);
    }

    public function setOwnerId($ownerId)
    {
        return $this->setAttribute(self::OWNER_ID, $ownerId);
    }

}