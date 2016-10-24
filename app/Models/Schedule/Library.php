<?php

namespace RZP\Models\Schedule;

use Illuminate\Support\Facades\App;
use RZP\Models\Settlement\Holidays;
use RZP\Trace\TraceCode;
use Carbon\Carbon;

class Library
{
    public static function getNextApplicableTime($currentTime, $merchant)
    {
        $schedule = $merchant->schedule;

        if ($schedule === null)
        {
            $addDays = $merchant->getSettlementSchedule();

            $settledAt = (new \RZP\Models\Transaction\Core)->calculateSettledAtTimestamp($currentTime, $addDays);
        }
        else
        {
            $settledAt = self::getNextApplicableTimeFromSchedule($currentTime, $schedule);
        }

        return $settledAt;
    }

    public static function getNextApplicableTimeFromSchedule($currentTime, $schedule)
    {
        App::getFacadeRoot()['trace']->info(
                                        TraceCode::SCHEDULE_RESOLUTION_INITIATED,
                                        compact('currentTime', 'schedule')
                                        );

        $settledAt = self::getMinimumDelayedTime($currentTime, $schedule);

        $nextRun = $schedule->getNextRun();

        $nextRun = Carbon::createFromTimestamp($nextRun, 'Asia/Kolkata');

        if ($settledAt > $nextRun)
        {
            $nextRun = self::computeFutureRun($schedule, $settledAt, $nextRun);
        }

        return $nextRun->getTimeStamp();
    }


    // ----------------------- Protected methods -----------------------

    protected static function computeFutureRun($schedule, $settledAt, $nextRun)
    {
        if ($schedule->getAnchor() !== null)
        {
            $futureRun = self::resolveAnchored($settledAt, $schedule);
        }
        else
        {
            $futureRun = self::resolveUnAnchored($settledAt, $schedule, $nextRun);
        }

        return $futureRun;
    }

    protected static function resolveAnchored($settledAt, $schedule)
    {
        $settledAt = $settledAt->addDay()->hour(0)->minute(0)->second(0);

        $stepType = Steps::ANCHORED_STEP;

        $step = 'add' . $stepType;

        App::getFacadeRoot()['trace']->info(
                                        TraceCode::SCHEDULE_ANCHORED_RESOLUTION,
                                        compact('settledAt', 'schedule', 'step')
                                        );

        while (self::checkAnchor($settledAt, $schedule) === false)
        {
            $settledAt->$step();
        }
        while (Holidays::isWorkingDay($settledAt) === false)
        {
            $settledAt->$step();
        }

        return $settledAt;
    }

    protected static function resolveUnAnchored($settledAt, $schedule, $nextRun)
    {
        $step = self::getStep($schedule, Steps::NON_ANCHORED_STEPS);

        $interval = $schedule->getInterval();

        App::getFacadeRoot()['trace']->info(
                                        TraceCode::SCHEDULE_ANCHORED_RESOLUTION,
                                        compact('settledAt', 'schedule', 'nextRun', 'step', 'interval')
                                        );

        while ($settledAt > $nextRun)
        {
            $nextRun->$step($interval);
        }

        return $nextRun;
    }

    protected static function checkAnchor($time, $schedule)
    {
        if ($schedule->getAnchor() !== -1)
        {
            $check = Anchor::CHECKS[$schedule->getPeriod()];

            return ($time->$check === $schedule->getAnchor());
        }
        else
        {
            if ($schedule->getPeriod() === Period::MONTHLY_DATE)
            {
                return ($time->day === $time->lastOfMonth()->day);
            }
            else if ($schedule->getPeriod() === Period::MONTHLY_WEEK)
            {
                return ($time->day === $time->lastOfMonth(Carbon::Monday)->day);
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
                $current = $current->addDay()->hour(0)->minute(0)->second(0);

                while (Holidays::isWorkingDay($current) === false)
                {
                    $current->addDay();
                }
            }
        }
        else
        {
            while ($minimumDelay > 0)
            {
                $current->addDay();

                if (Holidays::isWorkingDay($current) === true)
                {
                    $minimumDelay--;
                }
            }
        }

        return $current;
    }

    protected static function getStep($schedule, $stepsArray)
    {
        $stepType = $stepsArray[$schedule->getPeriod()];

        $step = 'add' . $stepType;

        return $step;
    }

    protected static function getLastRun($schedule)
    {
        $lastRunTimestamp = $schedule->getLastRun();

        $lastRun = Carbon::createFromTimestamp($lastRunTimestamp, 'Asia/Kolkata');

        return $lastRun;
    }
}
