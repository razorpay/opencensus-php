<?php

namespace RZP\Models\Schedule;

use RZP\Models\Settlement\Holidays;
use Carbon\Carbon;

class Library
{
    public static function getNextApplicableTime($currentTime, $schedule)
    {
        // Minimum delay before the settlement of any payment. In case of hourly
        // schedules, this is set to zero, but settlement time is pushed forward
        // by an hour anyway to avoid race conditions.
        $settledAt = self::getMinimumDelayedTime($currentTime, $schedule);

        $nextRun = $schedule->getNextRun();

        $nextRun = Carbon::createFromTimestamp($nextRun, 'Asia/Kolkata');

        // If minimum delay is more than the time till next run of the settlement
        // schedule, then we calculate the *next* next run, and set that.
        if ($settledAt > $nextRun)
        {
            $nextRun = self::computeFutureRun($schedule, $settledAt, $nextRun);
        }

        return $nextRun->getTimestamp();
    }

    // ----------------------- Protected methods -----------------------

    protected static function computeFutureRun($schedule, $settledAt, $nextRun)
    {
        if ($schedule->getAnchor() !== null)
        {
            // Anchored schedules are those that rely on a certain attribute
            // of its target days. For example, settlements that happen every
            // Thursday, or the last Friday of every month.
            $futureRun = self::resolveAnchored($settledAt, $schedule);
        }
        else
        {
            // Unanchored schedules are those that are fixed on the basis of
            // the time between payment and settlement, or after a fixed period
            // of time. For example, settlements that happen N days after their
            // corresponding payments, or settlements that happen every N hours.
            $futureRun = self::resolveUnAnchored($settledAt, $schedule, $nextRun);
        }

        return $futureRun;
    }

    protected static function resolveAnchored($settledAt, $schedule)
    {
        // Since hourly schedules can't be anchored, time no longer matters.
        $settledAt = $settledAt->addDay()->hour(0)->minute(0)->second(0);

        // Step size may vary based on the period of the schedule
        $step = self::getStep($schedule, Steps::ANCHORED_STEPS);

        // Increment by step size until condition is met and we arrive
        // at an anchor date.
        while (self::checkAnchor($settledAt, $schedule) === false)
        {
            $settledAt->$step();
        }

        // If anchor date is a holiday, don't wait till next anchor
        // date. Settlement on the next working day.
        if (Holidays::isWorkingDay($settledAt) === false)
        {
            $settledAt = Holidays::getNextWorkingDay($settledAt);
        }

        return $settledAt;
    }

    protected static function resolveUnAnchored($settledAt, $schedule, $nextRun)
    {
        // Step size may vary based on the period of the schedule
        $step = self::getStep($schedule, Steps::NON_ANCHORED_STEPS);

        $interval = $schedule->getInterval();

        // Increment by interval until we cross minimum delay time.
        while ($settledAt > $nextRun)
        {
            $nextRun->$step($interval);
        }

        if (Holidays::isWorkingDay($nextRun) === false)
        {
            $nextRun = Holidays::getNextWorkingDay($nextRun);
        }

        return $nextRun;
    }

    protected static function checkAnchor($time, $schedule)
    {
        // -1 is used to denote 'last', for example the last day of month.
        if ($schedule->getAnchor() !== -1)
        {
            // Mapping for period to Carbon methods
            $check = Anchor::CHECKS[$schedule->getPeriod()];

            // For monthly-week periods, ensure that weekday is Monday
            if (($schedule->getPeriod() === Period::MONTHLY_WEEK) and
                ($time->dayOfWeek !== Carbon::MONDAY))
            {
                return false;
            }

            return ($time->$check === $schedule->getAnchor());
        }
        else
        {
            // Last date of the month
            if ($schedule->getPeriod() === Period::MONTHLY_DATE)
            {
                return ($time->day === $time->copy()->lastOfMonth()->day);
            }
            // Last week of the month
            else if ($schedule->getPeriod() === Period::MONTHLY_WEEK)
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
        }

        return $current;
    }

    // Get Carbon modifier
    protected static function getStep($schedule, $stepsArray)
    {
        $stepType = $stepsArray[$schedule->getPeriod()];

        $step = 'add' . $stepType;

        return $step;
    }

    // Get Carbon object for last_run
    protected static function getLastRun($schedule)
    {
        $lastRunTimestamp = $schedule->getLastRun();

        $lastRun = Carbon::createFromTimestamp($lastRunTimestamp, 'Asia/Kolkata');

        return $lastRun;
    }
}
