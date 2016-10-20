<?php

namespace RZP\Models\Schedule;

use RZP\Models\Merchant\Schedule as MerchantSchedule;
use Carbon\Carbon;

class Core
{
  public static function getNextApplicableTime($currentTime, $merchant)
  {
    $merchantSchedule = (new MerchantSchedule\Repository)->findByMerchantId($merchant->getId());

    if ($merchantSchedule === null)
    {
      $addDays = $merchant->getSettlementSchedule();

      $settledAt = (new \RZP\Models\Transaction\Core)->calculateSettledAtTimestamp($currentTime, $addDays);
    }
    else
    {
      $settledAt = self::getNextApplicableTimeFromSchedule($currentTime, $merchantSchedule);
    }

    return $settledAt;
  }

  protected static function getNextApplicableTimeFromSchedule($currentTime, $merchantSchedule)
  {
    $schedule = $merchantSchedule->schedule();

    $schedule = (new Repository)->findOrFailPublic($merchantSchedule->getScheduleId());

    $settledAt = self::getMinimumDelayedTime($currentTime, $schedule);

    if ($schedule->getAnchor() !== null)
    {
      $nextRun = self::resolveAnchored($settledAt, $schedule);
    }
    else
    {
      $nextRun = self::resolveUnAnchored($settledAt, $schedule, $merchantSchedule);
    }

    return $nextRun->getTimeStamp();
  }

  protected static function resolveAnchored($settledAt, $schedule)
  {
    $settledAt = $settledAt->hour(0)->minute(0)->second(0);

    $step = self::getAnchoredStep($schedule);

    while(self::checkAnchor($settledAt, $schedule) === false)
    {
      $settledAt->$step();
    }

    return $settledAt;
  }

  protected static function resolveUnAnchored($settledAt, $schedule, $merchantSchedule)
  {
    $lastRun = self::getLastRun($merchantSchedule);

    $step = self::getNonAnchoredStep($schedule);

    $interval = $schedule->getInterval();

    while($settledAt > $lastRun)
    {
      $lastRun->$step($interval);
    }

    self::updateLastRun($merchantSchedule, $lastRun);

    return $lastRun;
  }

  protected static function updateLastRun($merchantSchedule, $lastRun)
  {
    $merchantSchedule = (new MerchantSchedule\Repository)->findOrFailPublic($merchantSchedule->getId());

    $merchantSchedule->setLastRun($lastRun->getTimeStamp());

    $merchantSchedule->saveOrFail();
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

    $settledAt = Carbon::createFromTimestamp($currentTime)->addSeconds($minimumDelay);

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

  protected static function getLastRun($merchantSchedule)
  {
    $lastRunTimestamp = $merchantSchedule->getLastRun();

    $lastRun = Carbon::createFromTimestamp($lastRunTimestamp);

    return $lastRun;
  }
}
