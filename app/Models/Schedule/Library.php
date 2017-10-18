<?php

namespace RZP\Models\Schedule;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Holidays;
use Carbon\Carbon;
use RZP\Constants\Timezone;

class Library
{
    public static function getNextApplicableTime(int $currentTime, Entity $schedule, $nextRunAt) : int
    {
        //
        // Minimum delay before the settlement of any payment. In case of hourly
        // schedules, this is set to zero, but settlement time is pushed forward
        // by an hour anyway to avoid race conditions.
        //
        $settledAt = self::getMinimumDelayedTime($currentTime, $schedule);

        $nextRun = Carbon::createFromTimestamp($nextRunAt, Timezone::IST);

        //
        // If minimum delay is more than the time till next run of the settlement
        // schedule, then we calculate the *next* next run, and set that.
        //
        if ($settledAt > $nextRun)
        {
            $nextRun = self::computeFutureRun($schedule, $settledAt, $nextRun);
        }

        return $nextRun->getTimestamp();
    }

    public static function computeFutureRun(
        Entity $schedule,
        Carbon $referenceTime,
        Carbon $lastRun,
        bool $considerHolidays = true)
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
            $futureRun = self::resolveUnAnchored($schedule, $referenceTime, $lastRun);
        }

        if ($considerHolidays === true)
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
            // Since hourly schedules can't be anchored,
            // time no longer matters.
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

    protected static function resolveUnAnchored(Entity $schedule, Carbon $refTime, Carbon $lastRun)
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
                    'last_run' => $lastRun->getTimestamp(),
                ]);
        }

        // Step size may vary based on the period of the schedule
        $step = Steps::getStep($schedule->getPeriod());

        $interval = $schedule->getInterval();

        // Increment by interval until we cross minimum delay time.
        while ($refTime > $lastRun)
        {
            $lastRun->$step($interval);
        }

        return $lastRun;
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
     * @throws LogicException
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

    protected static function getMinimumDelayedTime(int $currentTime, Entity $schedule): Carbon
    {
        $current = Carbon::createFromTimestamp($currentTime, Timezone::IST);

        $minimumDelay = $schedule->getDelay();

        if ($schedule->isHourly() === true)
        {
            //
            // Hourly schedules have delays in hours
            //
            $current->addHour($minimumDelay);

            //
            // Adding a few hours resulted in a holiday.
            // Now jump forward in days instead of hours.
            //
            if (Holidays::isWorkingDay($current) === false)
            {
                $current = Holidays::getNextWorkingDay($current);
            }
        }
        else
        {
            // Delay of N days means N working days.
            $current = Holidays::getNthWorkingDayFrom($current, $minimumDelay);

            $current->hour($schedule->getHour());
        }

        return $current;
    }
}
