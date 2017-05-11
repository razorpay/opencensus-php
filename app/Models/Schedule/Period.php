<?php

namespace RZP\Models\Schedule;

class Period
{
    const HOURLY       = 'hourly';

    const DAILY        = 'daily';

    const WEEKLY       = 'weekly';

    const MONTHLY      = 'monthly';

    const MONTHLY_DATE = 'monthly-date';

    const MONTHLY_WEEK = 'monthly-week';

    const PERIOD_LIST = [
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::MONTHLY_DATE,
        self::MONTHLY_WEEK,
    ];

    const ANCHORED_PERIODS = [
        self::WEEKLY,
        self::MONTHLY,
        self::MONTHLY_DATE,
        self::MONTHLY_WEEK,
    ];

    public static function isPeriodValid(string $period)
    {
        return (in_array($period, self::PERIOD_LIST, true));
    }
}
