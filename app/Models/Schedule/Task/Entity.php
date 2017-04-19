<?php

namespace RZP\Models\Schedule\Task;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Schedule\Library;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const TYPE              = 'type';
    const METHOD            = 'method';
    const SCHEDULE_ID       = 'schedule_id';
    const NEXT_RUN_AT       = 'next_run_at';
    const LAST_RUN_AT       = 'last_run_at';

    protected $entity = 'schedule_task';

    public $incrementing = true;

    protected $fillable = [
        self::TYPE,
        self::METHOD,
        self::NEXT_RUN_AT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::METHOD,
        self::SCHEDULE_ID,
        self::NEXT_RUN_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::METHOD,
        self::SCHEDULE_ID,
        self::NEXT_RUN_AT
    ];

    protected $defaults = [
        self::METHOD        => null,
        self::TYPE          => Type::SETTLEMENT,
        self::NEXT_RUN_AT   => null,
        self::LAST_RUN_AT   => null,
    ];

    protected static $modifiers = array(
        self::NEXT_RUN_AT,
    );

    protected $casts = [
        self::NEXT_RUN_AT => 'int',
    ];

    // ----------------------- Associations ------------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    // ----------------------- Modifiers ---------------------------------------

    protected function modifyNextRunAt(& $input)
    {
        if (isset($input[self::NEXT_RUN_AT]) === false)
        {
            $nextRunAt = Carbon::today('Asia/Kolkata')->timestamp;

            $input[self::NEXT_RUN_AT] = $nextRunAt;
        }
    }

    // ---------------------- Getters ------------------------------------------

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getScheduleId()
    {
        return $this->getAttribute(self::SCHEDULE_ID);
    }

    public function getNextRunAt()
    {
        return $this->getAttribute(self::NEXT_RUN_AT);
    }

    // -------------------------- Setters --------------------------------------

    public function setType($type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    public function setNextRunAt($timestamp)
    {
        return $this->setAttribute(self::NEXT_RUN_AT, $timestamp);
    }

    public function setLastRunAt($timestamp)
    {
        return $this->setAttribute(self::LAST_RUN_AT, $timestamp);
    }

    // ------------------------- Helper methods --------------------------------

    public function updateNextRunAndLastRun($considerHolidays = true)
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), 'Asia/Kolkata');

        $currentTime = Carbon::now('Asia/Kolkata');

        $nextRun = Library::computeFutureRun($this->schedule, $currentTime, $lastRun);

        $this->setNextRunAt($nextRun->timestamp);
        $this->setLastRunAt($lastRun->timestamp);
    }

    public function isTypeSettlement()
    {
        return ($this->getType() === Type::SETTLEMENT);
    }
}
