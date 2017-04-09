<?php

namespace RZP\Models\Schedule;

use RZP\Models\Settlement\Holidays;
use Carbon\Carbon;

class Library
{
    public static function getNextApplicableTime($currentTime, $schedule, $nextRunAt)
    {
        // Minimum delay before the settlement of any payment. In case of hourly
        // schedules, this is set to zero, but settlement time is pushed forward
        // by an hour anyway to avoid race conditions.

        $settledAt = self::getMinimumDelayedTime($currentTime, $schedule);

        $nextRun = Carbon::createFromTimestamp($nextRunAt, 'Asia/Kolkata');

        // If minimum delay is more than the time till next run of the settlement
        // schedule, then we calculate the *next* next run, and set that.
        if ($settledAt > $nextRun)
        {
            $nextRun = self::computeFutureRun($schedule, $settledAt, $nextRun);
        }

        return $nextRun->getTimestamp();
    }

    public static function computeFutureRun($schedule, $referenceTime, $nextRun)
    {
        if ($schedule->getAnchor() !== null)
        {
            // Anchored schedules are those that rely on a certain attribute
            // of its target days. For example, settlements that happen every
            // Thursday, or the last Friday of every month.
            $futureRun = self::resolveAnchored($referenceTime, $schedule);
        }
        else
        {
            // Unanchored schedules are those that are fixed on the basis of
            // the time between payment and settlement, or after a fixed period
            // of time. For example, settlements that happen N days after their
            // corresponding payments, or settlements that happen every N hours.
            $futureRun = self::resolveUnAnchored($referenceTime, $schedule, $nextRun);
        }

        // If anchor date is a holiday, don't wait till next anchor
        // date. Settlement on the next working day.
        if (Holidays::isWorkingDay($futureRun) === false)
        {
            $futureRun = Holidays::getNextWorkingDay($futureRun);
        }

        if ($schedule->isHourly() === false)
        {
            // set the hour for future run from schedule
            $futureRun->hour($schedule->getHour());
        }

        return $futureRun;
    }

    protected static function resolveAnchored($refTime, $schedule)
    {
        // Since hourly schedules can't be anchored, time no longer matters.
        $nextRun = $refTime->addDay()->hour(0)->minute(0)->second(0);

        // Step size may vary based on the period of the schedule
        $step = self::getStep($schedule);

        // Increment by step size until condition is met and we arrive
        // at an anchor date.
        while (self::checkAnchor($nextRun, $schedule) === false)
        {
            $nextRun->$step();
        }

        return $nextRun;
    }

    protected static function resolveUnAnchored($refTime, $schedule, $nextRun)
    {
        // Step size may vary based on the period of the schedule
        $step = self::getStep($schedule);

        $interval = $schedule->getInterval();

        // Increment by interval until we cross minimum delay time.
        while ($refTime > $nextRun)
        {
            $nextRun->$step($interval);
        }

        return $nextRun;
    }

    protected static function checkAnchor($time, $schedule)
    {
        $period = $schedule->getPeriod();

        // -1 is used to denote 'last', for example the last day of month.
        if ($schedule->getAnchor() !== -1)
        {
            // Mapping for period to Carbon methods
            $check = Anchor::CHECKS[$schedule->getPeriod()];

            // For monthly-week periods, ensure that weekday is Monday
            if (($period === Period::MONTHLY_WEEK) and
                ($time->dayOfWeek !== Carbon::MONDAY))
            {
                return false;
            }

            return ($time->$check === $schedule->getAnchor());
        }
        else
        {
            // Last date of the month
            if (($period === Period::MONTHLY_DATE) or
                ($period === Period::MONTHLY))
            {
                return ($time->day === $time->copy()->lastOfMonth()->day);
            }
            // Last week of the month
            else if ($period === Period::MONTHLY_WEEK)
            {
                return ($time->day === $time->copy()->lastOfMonth(Carbon::MONDAY)->day);
            }
        }
    }

    protected static function getMinimumDelayedTime($currentTime, $schedule)
    {
        $current = Carbon::createFromTimestamp($currentTime, 'Asia/Kolkata');

        $minimumDelay = $schedule->getDelay();

        if ($minimumDelay === 0)
        {
            // Avoiding zero delay to prevent race conditions
            $current->addHour();

            // Adding a single hour resulted in a holiday.
            // Now jump forward in days instead of hours.
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

    // Get Carbon modifier
    protected static function getStep($schedule)
    {
        $stepType = Steps::STEP_LIST[$schedule->getPeriod()];

        $step = 'add' . $stepType;

        return $step;
    }
}
