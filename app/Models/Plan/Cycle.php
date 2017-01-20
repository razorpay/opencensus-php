<?php

namespace RZP\Models\Plan;

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

    public static function isPeriodValid($period)
    {
        if (in_array($period, self::$validPeriods) === true)
        {
            return true;
        }

        return false;
    }

    public static function getMaxAllowedInterval($period)
    {
        if (self::isPeriodValid($period) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid argument for period passed', null, ['period' => $period]
            );
        }

        return self::$allowedMaxInterval[$period];
    }
}
