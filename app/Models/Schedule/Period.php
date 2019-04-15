<?php

namespace RZP\Models\Schedule;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Period
{
    const HOURLY       = 'hourly';

    const DAILY        = 'daily';

    const WEEKLY       = 'weekly';

    const MONTHLY      = 'monthly';

    const MONTHLY_DATE = 'monthly-date';

    const MONTHLY_WEEK = 'monthly-week';

    const YEARLY       = 'yearly';

    const MINUTE      = 'minute';

    const PERIOD_LIST = [
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::MONTHLY_DATE,
        self::MONTHLY_WEEK,
        self::YEARLY,
        self::MINUTE,
    ];

    const ANCHORED_PERIODS = [
        self::WEEKLY,
        self::MONTHLY,
        self::MONTHLY_DATE,
        self::MONTHLY_WEEK,
        self::YEARLY,
    ];

    public static function isPeriodAnchored(string $period)
    {
        return (in_array($period, self::ANCHORED_PERIODS, true) === true);
    }

    public static function isPeriodUnAnchored(string $period)
    {
        return (self::isPeriodAnchored($period) === false);
    }

    public static function isPeriodValid(string $period)
    {
        return (in_array($period, self::PERIOD_LIST, true));
    }

    public static function validatePeriod(string $period)
    {
        if (self::isPeriodValid($period) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_INVALID_PERIOD,
                'period',
                [
                    'period' => $period,
                ]);
        }
    }
}
