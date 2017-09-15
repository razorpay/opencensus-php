<?php

namespace RZP\Models\Schedule\Task;

use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Schedule\Library;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const TYPE              = 'type';
    const METHOD            = 'method';
    const SCHEDULE_ID       = 'schedule_id';
    const NEXT_RUN_AT       = 'next_run_at';
    const LAST_RUN_AT       = 'last_run_at';
    const DELETED_AT        = 'deleted_at';

    const SCHEDULE_NAME     = 'schedule_name';

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
        self::SCHEDULE_NAME,
        self::NEXT_RUN_AT,
        self::LAST_RUN_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::METHOD,
        self::SCHEDULE_ID,
        self::NEXT_RUN_AT,
        self::LAST_RUN_AT,
    ];

    protected $defaults = [
        self::METHOD        => null,
        self::TYPE          => Type::SETTLEMENT,
        self::NEXT_RUN_AT   => null,
        self::LAST_RUN_AT   => null,
    ];

    protected static $modifiers = array(
        self::METHOD,
        self::NEXT_RUN_AT,
    );

    protected $appends = [
        self::SCHEDULE_NAME,
    ];

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

    protected function modifyMethod(& $input)
    {
        // converts the whitespaces to null
        if (empty($input[self::METHOD]) === true)
        {
            $input[self::METHOD] = null;
        }
    }

    protected function modifyNextRunAt(& $input)
    {
        if (isset($input[self::NEXT_RUN_AT]) === false)
        {
            $nextRunAt = Carbon::today(Timezone::IST)->getTimestamp();

            $input[self::NEXT_RUN_AT] = $nextRunAt;
        }
    }

    protected function getScheduleNameAttribute()
    {
        if ($this->getScheduleId() === null)
        {
            return '';
        }

        return $this->schedule->getName();
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

    public function setLastRunAt(int $timestamp)
    {
        return $this->setAttribute(self::LAST_RUN_AT, $timestamp);
    }

    // ------------------------- Helper methods --------------------------------

    public function updateNextRunAndLastRun($considerHolidays = true)
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), Timezone::IST);

        $currentTime = Carbon::now(Timezone::IST);

        $nextRun = Library::computeFutureRun($this->schedule, $currentTime, $lastRun->copy(), $considerHolidays);

        $this->setNextRunAt($nextRun->getTimestamp());

        if ($lastRun !== null)
        {
            $this->setLastRunAt($lastRun->getTimestamp());
        }
    }

    /**
     * NOTE: This function does not take holidays into consideration.
     * It also updates the last run. So, calculations for next_run based
     * on the last_run may not end up correct. BE CAREFUL.
     */
    public function incrementNextRunByOneDayAndUpdateLastRun()
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), Timezone::IST);

        $nextRun = $lastRun->copy()->addDay();

        $this->setNextRunAt($nextRun->getTimestamp());
        $this->setLastRunAt($lastRun->getTimestamp());
    }

    public function updateNextRunAt($timestamp)
    {
        $schedule = $this->schedule;

        if ($schedule->hasHour() === true)
        {
            $nextRunAt = Carbon::createFromTimestamp($timestamp, Timezone::IST);

            $nextRunAt->hour($schedule->getHour());

            $this->setNextRunAt($nextRunAt->getTimestamp());
        }
    }

    public function isTypeSettlement()
    {
        return ($this->getType() === Type::SETTLEMENT);
    }
}
