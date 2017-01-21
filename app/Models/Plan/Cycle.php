<?php

namespace RZP\Models\Plan;

use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;

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

        $diffFunction = self::getCarbonDiffFunction($period);

        $diffInPeriod = $end->$diffFunction($start);

        //
        // We are adding one to the interval_count because
        // we would be charging on the start date also.
        //
        // (int) will always floor the value.
        //
        $totalCycles = (int) (($diffInPeriod/$interval) + 1);

        return $totalCycles;
    }

    protected static function getCarbonDiffFunction(string $period)
    {
        $carbonPeriod = self::$carbonFunctionMapping[$period];

        $diffFunction = 'diffIn' . $carbonPeriod;

        return $diffFunction;
    }
}
