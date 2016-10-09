<?php

namespace RZP\Models\Plan;

use RZP\Exception\BadRequestValidationFailureException;

class Interval
{
    const YEAR  = 'year';
    const MONTH = 'month';
    const WEEK  = 'week';
    const DAY   = 'day';

    const ONE_YEAR          = 1;
    const MONTHS_IN_YEAR    = 12;
    const WEEKS_IN_YEAR     = 52;
    const DAYS_IN_YEAR      = 365;

    protected static $validIntervals = [
        self::YEAR,
        self::MONTH,
        self::WEEK,
        self::DAY,
    ];

    protected static $allowedMaxInternalCount = [
        self::YEAR  => self::ONE_YEAR,
        self::MONTH => self::MONTHS_IN_YEAR,
        self::WEEK  => self::WEEKS_IN_YEAR,
        self::DAY   => self::DAYS_IN_YEAR,
    ];

    public static function isIntervalValid($interval)
    {
        if (in_array($interval, self::$validIntervals) === true)
        {
            return true;
        }

        return false;
    }

    public static function getMaxAllowedIntervalCount($interval)
    {
        if (self::isIntervalValid($interval) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid argument for interval passed', null, ['interval' => $interval]
            );
        }

        return self::$allowedMaxInternalCount[$interval];
    }
}