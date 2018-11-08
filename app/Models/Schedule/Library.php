<?php

namespace RZP\Models\Schedule;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Holidays;
use Carbon\Carbon;
use RZP\Constants\Timezone;

class Library
{
    public static function getNextApplicableTime(int $currentTime, Entity $schedule, $nextRunAt, bool $ignoreBankHolidays = false) : int
    {
        //
        // Minimum delay before the settlement of any payment. In case of hourly
        // schedules, this is set to zero, but settlement time is pushed forward
        // by an hour anyway to avoid race conditions.
        //
        $settledAt = self::getMinimumDelayedTime($currentTime, $schedule, $ignoreBankHolidays);

        $nextRun = Carbon::createFromTimestamp($nextRunAt, Timezone::IST);

        //
        // If minimum delay is more than the time till next run of the settlement
        // schedule, then we calculate the *next* next run, and set that.
        //
        if ($settledAt > $nextRun)
        {
            if ($schedule->getAnchor() != null)
            {
                $refTime = $settledAt;

                //
                // minTime is not needed because in anchored
                // schedules, we know exactly when to charge next.
                // The next_run is fixed without any dependency.
                //
                $minTime = null;
            }
            else
            {
                $refTime = $nextRun;

                $minTime = $settledAt;
            }

            $nextRun = self::computeFutureRun($schedule, $refTime, $minTime, $ignoreBankHolidays);
        }

        return $nextRun->getTimestamp();
    }

    /**
     * @param  Entity $schedule
     * @param  Carbon $referenceTime    This is used to calculate the next_run_at.
     *                                  The calculation happens from this timestamp.
     *                                  This would generally be the current time.
     *                                  But it can be a time in the future also
     *                                  from where we want to calculate the next_run_at.
     * @param  Carbon $minTime          The minimum time that needs to pass from the
     *                                  referenceTime to get the next_run. This field
     *                                  does not apply to anchored schedules.
     * @param  bool   $ignoreBankHolidays
     *
     * @return Carbon
     *
     * @throws LogicException
     */
    public static function computeFutureRun(
        Entity $schedule,
        Carbon $referenceTime,
        Carbon $minTime = null,
        bool $ignoreBankHolidays = false)
    {
        if ($schedule->getAnchor() !== null)
        {
            //
            // Anchored schedules are those that rely on a certain attribute
            // of its target days. For example, settlements that happen every
            // Thursday, or the last Friday of every month.
            //
            $futureRun = self::resolveAnchored($schedule, $referenceTime);
        }
        else
        {
            //
            // Un-anchored schedules are those that are fixed on the basis of
            // the time between payment and settlement, or after a fixed period
            // of time. For example, settlements that happen N days after their
            // corresponding payments, or settlements that happen every N hours.
            //
            $futureRun = self::resolveUnAnchored($schedule, $referenceTime, $minTime);
        }

        if ($ignoreBankHolidays === false)
        {
            // If anchor date is a holiday, don't wait till next anchor
            // date. Settlement on the next working day.
            if (Holidays::isWorkingDay($futureRun) === false)
            {
                $futureRun = Holidays::getNextWorkingDay($futureRun);
            }
        }

        if ($schedule->hasHour() === true)
        {
            // set the hour for future run from schedule
            $futureRun->hour($schedule->getHour());
        }

        return $futureRun;
    }

    /**
     * The reason why we calculate next_run_at by incrementing
     * one day at a time is:
     *
     * RefTime can change because of holidays and stuff.
     * If schedule is to be run 20th of every month and the current RefTime
     * is 20th March and 20th April is a holiday, the RefTime will then
     * become 21st April. From then onwards, the schedule will become
     * 21st of every month!
     *
     * A solution to this is setting the day of the month to the anchor.
     * But, this won't work too since the refTime can spill over to the
     * next month also.
     * For example, if a schedule is to be run on 30th every month and
     * the current RefTime is 30th March and 30th April is a holiday,
     * the RefTime will become 1st May. The next RefTime should ideally be
     * 30th May, but due to holidays, the next RefTime will become 1st June.
     *
     * In some cases like in subscriptions, a day might get added to RefTime.
     * This causes similar issues like holidays.
     *
     * @param Entity $schedule
     * @param Carbon $refTime
     *
     * @return Carbon
     * @throws LogicException
     */
    protected static function resolveAnchored(Entity $schedule, Carbon $refTime): Carbon
    {
        $period = $schedule->getPeriod();

        if (Period::isPeriodUnAnchored($period) === true)
        {
            throw new LogicException(
                'Period should be un-anchored. Should not have reached here',
                ErrorCode::SERVER_ERROR_PERIOD_NOT_ANCHORED,
                [
                    'period'        => $period,
                    'schedule_id'   => $schedule->getId(),
                    'ref_time'      => $refTime->getTimestamp(),
                ]);
        }

        $interval = $schedule->getInterval();

        //
        // Not sure when the interval would be null or 0. Mostly it should always
        // be 1 or more. Keeping this here just in case, since it's nullable.
        //
        if (empty($interval) === true)
        {
            $interval = 1;
        }

        //
        // Step size may vary based on the period of the schedule
        //
        $step = Steps::getStep($schedule->getPeriod());

        //
        // range parameters are inclusive on both ends.
        //
        foreach (range(1, $interval) as $i)
        {
            //
            // Since hourly schedules can't be
            // anchored, time no longer matters.
            //
            $nextRun = $refTime->addDay()->startOfDay();

            //
            // Increment by step size until condition is
            // met and we arrive at an anchor date.
            //
            while (self::checkAnchor($nextRun, $schedule) === false)
            {
                $nextRun->$step();
            }

            $refTime = $nextRun;
        }

        return $nextRun;
    }

    protected static function resolveUnAnchored(Entity $schedule, Carbon $refTime, Carbon $minTime = null)
    {
        $period = $schedule->getPeriod();

        if (Period::isPeriodAnchored($period) === true)
        {
            throw new LogicException(
                'Period should be un-anchored. Should not have reached here',
                ErrorCode::SERVER_ERROR_PERIOD_NOT_UNANCHORED,
                [
                    'period' => $period,
                    'schedule_id' => $schedule->getId(),
                    'ref_time' => $refTime->getTimestamp(),
                    'min_time' => $minTime->getTimestamp(),
                ]);
        }

        // Step size may vary based on the period of the schedule
        $step = Steps::getStep($schedule->getPeriod());

        $interval = $schedule->getInterval();

        //
        // Problem: Some services (settlements) have a concept of `minTime` and some services (subscriptions) don't.
        // `minTime` is basically the minimum time that should be passed while calculating
        // the next run from a given time (`refTime`). If there was no `minTime`, we could just directly
        // do `refTime->addDays(3)`.
        // ---
        // Requirements:
        // 1. If minTime = 29th Jan 12am, refTime = 20th Jan 12am, we need the nextRun to be 29th Jan 12am.
        // 2. If minTime = null, refTime = 20th Jan 12am, we need the nextRun to be 23rd Jan 12am.
        //    This is the case where the service (like subscriptions) doesn't have any concept of minTime and just
        //    needs the nextRun to be calculated from the given refTime.
        // 3. If minTime = 20th Jan 12am, refTime = 20th Jan 12am, we need the nextRun to be 20th Jan 12am.
        //    This is a requirement for settlements.
        //    The case where minTime = refTime and minTime != null:
        //    refTime (time when the cron is supposed to run next): 21st Jan 12am
        //    captured_at: 20th Jan 9am
        //    delay: 1 day
        //    hour: 0 (this is the default one)
        //    minTime (captured_at + delay): 21st Jan 12am
        // ---
        // Solution:
        // - If minTime = null, just add the interval to refTime directly.
        // - If minTime is not null, keep adding the interval
        //   to refTime until the minTime is equal or crossed.
        //
        if ($minTime === null)
        {
            $refTime->$step($interval);
        }
        else
        {
            while ($minTime > $refTime)
            {
                $refTime->$step($interval);
            }
        }

        return $refTime;
    }

    protected static function checkAnchor(Carbon $time, Entity $schedule)
    {
        //
        // -1 is used to denote 'last',
        // for example the last day of month.
        //
        if ($schedule->getAnchor() !== -1)
        {
            return self::checkAnchorForNonLast($time, $schedule);
        }
        else
        {
            return self::checkAnchorForLast($time, $schedule);
        }
    }

    /**
     * @param Carbon $time
     * @param Entity $schedule
     *
     * @return bool
     */
    protected static function checkAnchorForNonLast(Carbon $time, Entity $schedule): bool
    {
        $period = $schedule->getPeriod();

        $anchor = $schedule->getAnchor();

        $function = 'checkAnchorForNonLast' . studly_case($period);

        return self::{$function}($time, $anchor);
    }

    protected static function checkAnchorForNonLastMonthlyWeek(Carbon $time, int $anchor): bool
    {
        if ($time->dayOfWeek === Anchor::MONTHLY_WEEK_DAY)
        {
            return true;
        }

        return false;
    }

    protected static function checkAnchorForNonLastWeekly(Carbon $time, int $anchor): bool
    {
        if ($time->dayOfWeek === $anchor)
        {
            return true;
        }

        return false;
    }

    protected static function checkAnchorForNonLastMonthly(Carbon $time, int $anchor): bool
    {
        //
        // If the anchor is 31, we should be scheduling at 31st of every month.
        // But, some months don't have 31 days (OMG).
        // Hence, we take the last of that month. The latest that is possible
        // for that month.
        // So, for the month of April, we will consider 30th as the anchor.
        // For the month of February, we will consider 28th/29th as the anchor.
        // We do this only if the month does not have as many days as specified
        // by the anchor.
        //

        $numberOfDaysInCurrentMonth = $time->daysInMonth;

        if ($numberOfDaysInCurrentMonth < $anchor)
        {
            $anchor = $numberOfDaysInCurrentMonth;
        }

        if ($time->day === $anchor)
        {
            return true;
        }

        return false;
    }

    protected static function checkAnchorForNonLastMonthlyDate(Carbon $time, int $anchor): bool
    {
        return self::checkAnchorForNonLastMonthly($time, $anchor);
    }

    protected static function checkAnchorForNonLastYearly(Carbon $time, int $anchor): bool
    {
        $anchorDay = $anchor % 100;
        $anchorMonth = (int) ($anchor / 100);

        Anchor::validateDayAndMonth($anchorDay, $anchorMonth);

        //
        // In case the anchor is set to Feb 29th,
        // we convert the anchor to Feb 28th for
        // all years except leap years. For leap
        // years, we keep it as it is.
        //
        if (($anchorMonth === 2) and
            ($anchorDay === 29) and
            ($time->isLeapYear() === false))
        {
            $anchorDay = 28;
        }

        if (($time->day === $anchorDay) and
            ($time->month === $anchorMonth))
        {
            return true;
        }

        return false;
    }

    protected static function checkAnchorForLast(Carbon $time, Entity $schedule): bool
    {
        $period = $schedule->getPeriod();

        switch($period)
        {
            case Period::MONTHLY:
            case Period::MONTHLY_DATE:
                return ($time->day === $time->copy()->lastOfMonth()->day);

            case Period::MONTHLY_WEEK:
                return ($time->day === $time->copy()->lastOfMonth(Anchor::MONTHLY_WEEK_DAY)->day);

            case Period::YEARLY:
                return ($time->day === $time->copy()->lastOfYear()->day);

            default:
                throw new LogicException(
                    'Invalid period. Should not have reached here.',
                    null,
                    [
                        'schedule_id'   => $schedule->getId(),
                        'anchor'        => $schedule->getAnchor(),
                        'period'        => $period,
                    ]);
        }
    }

    protected static function getMinimumDelayedTime(int $current, Entity $schedule, bool $ignoreBankHolidays = false): Carbon
    {
        $currentTime = Carbon::createFromTimestamp($current, Timezone::IST);

        $minimumDelayedTime = $currentTime->copy();

        $minimumDelay = $schedule->getDelay();

        if ($schedule->isHourly() === true)
        {
            //
            // Hourly schedules have delays in hours
            //
            $minimumDelayedTime->addHour($minimumDelay);

            //
            // Adding a few hours resulted in a holiday.
            // Now jump forward in days instead of hours.
            //
            if (($ignoreBankHolidays === false) and
                (Holidays::isWorkingDay($minimumDelayedTime) === false))
            {
                $minimumDelayedTime = Holidays::getNextWorkingDay($minimumDelayedTime);
            }
        }
        else
        {
            // Will be having 24x7 only for hourly schedules for now.
            // Will see about non-hourly schedules later.

            // Delay of N days means N working days.
            $minimumDelayedTime = Holidays::getNthWorkingDayFrom($minimumDelayedTime, $minimumDelay);

            //
            // Used to implement settlement slots during the day,
            // eg. 1pm settlements vs 9am settlements
            //
            $minimumDelayedTime->hour($schedule->getHour());

            //
            // For schedules with delay=0, it is possible that setting hour in
            // the previous line results in a time that's less than current time.
            //
            if ($minimumDelayedTime < $currentTime)
            {
                $minimumDelayedTime = $currentTime;
            }
        }

        return $minimumDelayedTime;
    }
}
