<?php

namespace RZP\Models\Schedule;

class Period
{
    const HOURLY       = 'hourly';

    const DAILY        = 'daily';

    const WEEKLY       = 'weekly';

    const MONTHLY_DATE = 'monthly-date';

    const MONTHLY_WEEK = 'monthly-week';

    const PERIOD_LIST = [
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY_DATE,
        self::MONTHLY_WEEK,
    ];
}
