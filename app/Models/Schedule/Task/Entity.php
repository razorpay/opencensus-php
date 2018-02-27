<?php

namespace RZP\Models\Schedule\Task;

use Illuminate\Database\Eloquent\SoftDeletes;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;

/**
 * @property Schedule\Entity $schedule
 */
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
            //
            // We need to set a default value here since some flows
            // are dependent on always having a value for this.
            // Examples: `updateNextRunAndLastRunFromGivenMinTimeAndRefTime`
            //
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

    public function getLastRunAt()
    {
        return $this->getAttribute(self::LAST_RUN_AT);
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

    public function updateNextRunAndLastRun(bool $considerHolidays = false)
    {
        $currentTime = Carbon::now(Timezone::IST);

        $schedulePeriod = $this->schedule->getPeriod();

        if (Schedule\Period::isPeriodAnchored($schedulePeriod) === true)
        {
            $refTime = $currentTime;
            $minTime = null;
        }
        else
        {
            $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), Timezone::IST);

            $refTime = $lastRun;
            $minTime = $currentTime;
        }

        $this->updateNextRunAndLastRunFromGivenMinTimeAndRefTime($refTime, $minTime, $considerHolidays);
    }

    /**
     * @param Carbon $refTime           reference time refers to the base time
     *                                  from which next run should be calculated
     * @param int|null $minTime
     * @param bool $considerHolidays
     *
     * @throws \RZP\Exception\LogicException
     */
    public function updateNextRunAndLastRunFromGivenMinTimeAndRefTime(
        $refTime,
        $minTime = null,
        $considerHolidays = false)
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), Timezone::IST);

        //
        // Even though we are are passing minTime here, for anchored
        // schedules, this will not be used and will be ignored completely.
        // Unanchored will work with/without the minTime.
        //
        $nextRun = Schedule\Library::computeFutureRun($this->schedule, $refTime, $minTime, $considerHolidays);

        $this->setNextRunAt($nextRun->getTimestamp());

        //
        // last run will never be null because it is
        // derived from next_run_at which will never be
        // null since it is set to midnight by default.
        //
        $this->setLastRunAt($lastRun->getTimestamp());
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

    /**
     * In case of retries, we would explicitly change the task's next_run_at
     * to the next day instead of next month or so. If the retry is successful,
     * we would call this function and the next_run_at will get set to
     * whatever it's supposed to get set to initially without retry.
     *
     * @param string $mode
     * @param bool $retry
     * @throws \RZP\Exception\LogicException
     */
    public function updateForSubscription(string $mode , $retry = false)
    {
        if ($retry === true)
        {
            $this->incrementNextRunByOneDayAndUpdateLastRun();

            return;
        }

        //
        // TODO: We should be able to use `getNextRunAt()` for Live Mode also.
        //
        $referenceTime = Carbon::now(Timezone::IST);

        if ($mode === Mode::TEST)
        {
            //
            // Calling updateNextRunAndLastRun for task sets the next_run starting
            // from current time. This works fine in most cases, since charge time
            // is usually equal to current time. But in the merchant-initiated test
            // charge flow, we allow merchants to simulate a future charge for a
            // subscription. So in this case, using current time will give the wrong
            // result. So we use charge_at instead, which is equal to current time
            // in normal flow, and equal to simulated current time in test charge flow.
            //
            // In case of auth transaction (immediate), charge_at would be null.
            // In that case, we can use actual current time as the reference time.
            //
            // Problem : In case it is the first auth transaction for which start at was null
            // next run at will be set to the creation date of the subscription. But we don't
            // want creation date of subscription as reference time to calculate the next run as
            // that will give us wrong next_run_at. To calculate right next_run_at reference should
            // be the time when the first auth transaction happens which would be equal to
            // current timestamp in this case.
            // Solution : next_run  will be set to creation date of subscription by default
            // in case start_at is null. So when auth transaction happens in test mode it will always in
            // future time. And Carbon::now will always be greater than next_run_at. But in subsequent charges
            // either cron will charge the payment or the merchant. But if merchant is charging that mean next_run_at
            // hasn't happened yet. So Carbon::now will always be less than that of next_run_at
            //
            if ($this->getNextRunAt() > Carbon::now()->getTimestamp())
            {
                $referenceTime = $this->getNextRunAt();
            }
            else
            {
                $referenceTime = Carbon::now()->getTimestamp();
            }

            $referenceTime = Carbon::createFromTimestamp($referenceTime, Timezone::IST);
        }

        $this->updateNextRunAndLastRunFromGivenMinTimeAndRefTime($referenceTime);
    }

    public function isTypeSettlement()
    {
        return ($this->getType() === Type::SETTLEMENT);
    }
}
