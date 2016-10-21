<?php

namespace RZP\Models\Schedule;

use Illuminate\Support\Facades\App;
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

  protected static function getNextApplicableTimeFromSchedule($currentTime, $schedule)
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

  protected static function computeFutureRun($schedule, $settledAt, $nextRun)
  {
    if ($schedule->getAnchor() !== null)
    {
      $futureRun = self::resolveAnchored($settledAt, $schedule);
    }
    else
    {
      $futureRun = self::resolveUnAnchored($settledAt, $schedule);
    }

    return $futureRun;
  }

  protected static function resolveAnchored($settledAt, $schedule)
  {
    $settledAt = $settledAt->hour(0)->minute(0)->second(0);

    $step = self::getAnchoredStep($schedule);

    App::getFacadeRoot()['trace']->info(
                                        TraceCode::SCHEDULE_ANCHORED_RESOLUTION,
                                        compact('settledAt', 'schedule', 'step')
                                      );

    while(self::checkAnchor($settledAt, $schedule) === false)
    {
      $settledAt->$step();
    }

    return $settledAt;
  }

  protected static function resolveUnAnchored($settledAt, $schedule, $nextRun)
  {
    $step = self::getNonAnchoredStep($schedule);

    $interval = $schedule->getInterval();

    App::getFacadeRoot()['trace']->info(
                              TraceCode::SCHEDULE_ANCHORED_RESOLUTION,
                              compact('settledAt', 'schedule', 'nextRun', 'step', 'interval')
                            );

    while($settledAt > $lastRun)
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
    $minimumDelay = $schedule->getDelay();

    $settledAt = Carbon::createFromTimestamp($currentTime, 'Asia/Kolkata')->addSeconds($minimumDelay);

    return $settledAt;
  }

  protected static function getNonAnchoredStep($schedule)
  {
    $stepType = Steps::NON_ANCHORED_STEPS[$schedule->getPeriod()];

    $step = 'add' . $stepType;

    return $step;
  }

  protected static function getAnchoredStep($schedule)
  {
    $stepType = Steps::ANCHORED_STEPS[$schedule->getPeriod()];

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
