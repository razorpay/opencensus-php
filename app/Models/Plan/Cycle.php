<?php

namespace RZP\Models\Plan;

use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\LogicException;
use RZP\Models\Schedule\Library;

class Cycle
{
    const YEARLY  = 'yearly';
    const MONTHLY = 'monthly';
    const WEEKLY  = 'weekly';
    const DAILY   = 'daily';

    const ONE_YEAR          = 1;
    const MONTHS_IN_YEAR    = 12;
    const WEEKS_IN_YEAR     = 52;
    // TODO: This can be 366 too. Fix.
    const DAYS_IN_YEAR      = 365;

    protected static $validPeriods = [
        self::YEARLY,
        self::MONTHLY,
        self::WEEKLY,
        self::DAILY,
    ];

    protected static $allowedMaxInterval = [
        self::YEARLY  => self::ONE_YEAR,
        self::MONTHLY => self::MONTHS_IN_YEAR,
        self::WEEKLY  => self::WEEKS_IN_YEAR,
        self::DAILY   => self::DAYS_IN_YEAR,
    ];

    protected static $carbonFunctionMapping = [
        self::YEARLY    => 'years',
        self::MONTHLY   => 'months',
        self::WEEKLY    => 'weeks',
        self::DAILY     => 'days',
    ];

    public static function isPeriodValid(string $period)
    {
        if (in_array($period, self::$validPeriods, true) === true)
        {
            return true;
        }

        return false;
    }

    public static function getMaxAllowedInterval(string $period)
    {
        if (self::isPeriodValid($period) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid argument for period passed', null, ['period' => $period]
            );
        }

        return self::$allowedMaxInterval[$period];
    }

    public static function getTotalCountForGivenInterval(Subscription\Entity $subscription)
    {
        $start = $subscription->getStartAt();
        $end = $subscription->getEndAt();

        $schedule = $subscription->schedule;

        $start = Carbon::createFromTimestamp($start, 'Asia/Kolkata');
        $end = Carbon::createFromTimestamp($end, 'Asia/Kolkata');

        $nextRun = $start;

        //
        // We are starting with 1 because we would be charging
        // on the start_date also.
        //
        $totalCount = 1;

        while ($nextRun < $end)
        {
            $nextRun = Library::computeFutureRun($schedule, $start, $start, false);

            $start = $nextRun;

            $totalCount++;
        }

        return $totalCount;
    }

    public static function getEndTimeForGivenTotalCount(Subscription\Entity $subscription)
    {
        $schedule = $subscription->schedule;

        $start = $subscription->getStartAt();
        $totalCount = $subscription->getTotalCount();

        $start = Carbon::createFromTimestamp($start, 'Asia/Kolkata');

        //
        // We are subtracting one because we would be
        // charging on the start date also.
        // The total count given would be inclusive of the
        // charge made on start date also.
        //
        foreach (range(1, $totalCount - 1) as $i)
        {
            $nextRun = Library::computeFutureRun($schedule, $start, $start, false);

            $start = $nextRun;
        }

        $end = $start->timestamp;

        return $end;
    }

    public static function getCarbonFunction(string $period, string $operation)
    {
        switch ($operation)
        {
            case 'diff':
                $carbonFunction = 'diffIn';

                break;
            case 'add':
                $carbonFunction = 'add';

                break;
            default:
                throw new LogicException(
                    'Invalid operation provided for getting Carbon function',
                    null,
                    [
                        'period' => $period,
                        'operation' => $operation,
                    ]);
        }

        $carbonPeriod = self::$carbonFunctionMapping[$period];

        $carbonFunction .= $carbonPeriod;

        return $carbonFunction;
    }
}
