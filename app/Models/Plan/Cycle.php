<?php

namespace RZP\Models\Plan;

use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\LogicException;

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
        if (in_array($period, self::$validPeriods) === true)
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

    public static function getTotalCountForGivenInterval(Entity $plan, int $start, int $end)
    {
        $interval = $plan->getInterval();
        $period = $plan->getPeriod();

        $start = Carbon::createFromTimestamp($start, 'Asia/Kolkata');
        $end = Carbon::createFromTimestamp($end, 'Asia/Kolkata');

        $diffFunction = self::getCarbonFunction($period, 'diff');

        $diffInPeriod = $end->$diffFunction($start);

        //
        // We are adding one to the interval_count because
        // we would be charging on the start date also.
        //
        // (int) will always floor the value.
        //
        $totalCycles = (int) (($diffInPeriod / $interval) + 1);

        return $totalCycles;
    }

    public static function getEndTimeForGivenTotalCount(Entity $plan, int $start, int $totalCount)
    {
        $interval = $plan->getInterval();
        $period = $plan->getPeriod();

        $start = Carbon::createFromTimestamp($start, 'Asia/Kolkata');

        $addFunction = self::getCarbonFunction($period, 'add');

        //
        // We are subtracting one because we would be
        // charging on the start date also.
        // The total count given would be inclusive of the
        // charge made on start date also.
        //
        $toAdd = ($totalCount * $interval) - 1;

        $end = $start->$addFunction($toAdd)->timestamp;

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
