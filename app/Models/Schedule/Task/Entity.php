<?php

namespace RZP\Models\Schedule\Task;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Models\Plan\Subscription;

/**
 * Class Entity
 *
 * @package RZP\Models\Schedule\Task
 *
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
    const INTERNATIONAL     = 'international';
    const SCHEDULE_ID       = 'schedule_id';
    const NEXT_RUN_AT       = 'next_run_at';
    const LAST_RUN_AT       = 'last_run_at';
    const DELETED_AT        = 'deleted_at';
    const BALANCE_ID        = 'balance_id';

    const SCHEDULE_NAME     = 'schedule_name';

    protected $entity = 'schedule_task';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $fillable = [
        self::TYPE,
        self::METHOD,
        self::NEXT_RUN_AT,
        self::INTERNATIONAL,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::TYPE,
        self::METHOD,
        self::INTERNATIONAL,
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
        self::INTERNATIONAL => 0,
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

    protected $ignoredRelations = [
        'entity',
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
            // Examples: `updateNextRunAndLastRunFromGivenMinTimeAndRefTime`,
            // `updateForSubscription`
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

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getNextRunAt()
    {
        return $this->getAttribute(self::NEXT_RUN_AT);
    }

    public function getLastRunAt()
    {
        return $this->getAttribute(self::LAST_RUN_AT);
    }

    public function isInternational()
    {
        return (bool) $this->getAttribute(self::INTERNATIONAL);
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

    public function setEntityId(string $entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setEntityType(string $entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    // ------------------------- Helper methods --------------------------------

    public function updateNextRunAndLastRun(bool $ignoreHolidays = true)
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

        $this->updateNextRunAndLastRunFromGivenMinTimeAndRefTime($refTime, $minTime, $ignoreHolidays);
    }

    /**
     * @param Carbon   $refTime           reference time refers to the base time
     *                                  from which next run should be calculated
     * @param int|null $minTime
     * @param bool     $ignoreHolidays
     *
     * @throws \RZP\Exception\LogicException
     */
    public function updateNextRunAndLastRunFromGivenMinTimeAndRefTime(
        $refTime,
        $minTime = null,
        $ignoreHolidays = false)
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRunAt(), Timezone::IST);

        //
        // Even though we are are passing minTime here, for anchored
        // schedules, this will not be used and will be ignored completely.
        // Unanchored will work with/without the minTime.
        //
        $nextRun = Schedule\Library::computeFutureRun($this->schedule, $refTime, $minTime, $ignoreHolidays);

        $this->setNextRunAt($nextRun->getTimestamp());

        //
        // lastRun will never be null because it is
        // derived from next_run_at which will never be
        // null since it is set to midnight by default.
        // But, the condition is here nevertheless.
        //
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

    /**
     * In case of retries, we would explicitly change the task's next_run_at
     * to the next day instead of next month or so. If the retry is successful,
     * we would call this function and the next_run_at will get set to
     * whatever it's supposed to get set to initially without retry.
     *
     * @param Subscription\Entity $subscription
     * @param string              $mode
     * @param bool                $retry
     * @throws \RZP\Exception\LogicException
     */
    public function updateForSubscription(Subscription\Entity $subscription, string $mode, $retry = false)
    {
        if ($retry === true)
        {
            $this->incrementNextRunByOneDayAndUpdateLastRun();

            return;
        }

        //
        // Calling updateNextRunAndLastRun for task sets the next_run starting
        // from current time. This works fine in most cases, since charge time
        // is usually equal to current time. But in the merchant-initiated test
        // charge flow, we allow merchants to simulate a future charge for a
        // subscription. So in this case, using current time will give the wrong
        // result. So we use charge_at (next_run_at) instead, which is equal to
        // current time in normal flow, and equal to simulated current time in
        // test charge flow.
        //
        // But, using next_run_at creates an issue when auth transaction (immediate) is made.
        //
        // In case of auth transaction (immediate), start_at would be null.
        // Since start_at is null, we don't set any next_run_at during subscription
        // creation (If it was not null, we would have set the next_run_at to the
        // start_at value). Since we don't set next_run_at, the task entity sets the
        // next_run_at to a default value: midnight. In this case, it would get set
        // to subscription's creation date's midnight -- hence, in the past. We cannot
        // use this value as refTime since it's not the actual next_run_at, but a dummy one.
        //
        // Solution: We know for a fact that next_run_at can NEVER be lesser than the
        // subscription start_at value. If it is, it means that it's the auth
        // transaction (immediate) where we haven't gotten a chance to update next_run_at.
        // In this case, we just use the subscription's start_at time (which we do when
        // subscription is created with start_at value) to calculate the next_run_at.
        // In all other cases, we just use next_run_at as it is, because this value is set by us
        // explicitly when to run and in simulated flow, this is equivalent to current time itself.
        //
        if (($this->getNextRunAt() < $subscription->getStartAt()) or
            ($this->getNextRunAt() === null))
        {
            $referenceTime = $subscription->getStartAt();
        }
        else
        {
            //
            // We are not actually using next_run_at here because of unanchored
            // schedules. Anchored schedules have no issue with using current
            // time or next_run_at values. They both would have the same value
            // in live mode. In test mode, we can't use the current time
            // because of simulated charges. But, we can use next_run_at in
            // both test and live modes. But, next_run_at has an issue with
            // unanchored schedules. Let's take an example of a schedule having
            // 4 days interval. If the next_run_at was on 10th Jan and a
            // failure happened, the next_run_at will get updated to 11th Jan.
            // Once we run on 11th Jan and it goes through successfully, and if
            // we use next_run_at, the next run calculated would end up being
            // 15th Jan and not 14th Jan. Hence, we cannot use next_run_at to
            // calculate the next next_run_at. So, we use current_start instead.
            //
            // current_start will be last run of the current billing cycle.
            // It's always updated before reaching this point.
            //
            $referenceTime = $subscription->getCurrentStart();
        }

        $referenceTime = Carbon::createFromTimestamp($referenceTime, Timezone::IST);

        $this->updateNextRunAndLastRunFromGivenMinTimeAndRefTime($referenceTime, $minTime = null, $ignoreHolidays = true);
    }

    public function isTypeSettlement()
    {
        return ($this->getType() === Type::SETTLEMENT);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
