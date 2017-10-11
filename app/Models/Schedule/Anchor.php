<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Anchor
{
    /**
     * Keeping this protected and not a constant because
     * we don't want anyone to use this. This shouldn't be
     * used because this array is not exhaustive /standard
     * for different types of periods.
     * If an anchor is needed, we should use the function
     * `getAnchor` instead and not directly compute an
     * anchor using `checks`.
     *
     * @var array
     */
    protected static $checks = [
        Period::WEEKLY       => 'dayOfWeek',
        Period::MONTHLY_DATE => 'day',
        Period::MONTHLY      => 'day',
        Period::MONTHLY_WEEK => 'weekOfMonth',
    ];

    /**
     * For monthly-week periods, we only allow Mondays.
     */
    const MONTHLY_WEEK_DAY = Carbon::MONDAY;

    public static function getAnchor(string $period, Carbon $startTime = null): int
    {
        Period::validatePeriod($period);

        if ($startTime === null)
        {
            $startTime = Carbon::now(Timezone::IST);
        }

        if ($period === Period::YEARLY)
        {
            $anchor = self::getAnchorForYearly($startTime);
        }
        else
        {
            $check = self::$checks[$period];

            $anchor = $startTime->$check;
        }

        return $anchor;
    }

    public static function getAnchorForYearly(Carbon $startTime): int
    {
        $month = $startTime->month;
        $day = $startTime->day;

        $yearAnchor = self::getYearlyAnchorForDate(intval($day), intval($month));

        return $yearAnchor;
    }

    public static function validateDayAndMonth(int $day, int $month)
    {
        self::validateDay($day);
        self::validateMonth($month);

        //
        // Using leap year, because February.
        //
        $leapYear = 2016;

        //
        // We use 1 and not the actual day because Carbon will not
        // be able to create a date if someone sends 30 as day and
        // 2 as month. We only need a date with the given month and
        // year anyway.
        //
        $testDay = 1;

        $date = Carbon::createFromDate($leapYear, $month, $testDay, Timezone::IST);

        if ($day > $date->daysInMonth)
        {
            throw new BadRequestValidationFailureException(
                'Invalid day provided for the given month',
                null,
                [
                    'day'           => $day,
                    'month'         => $month,
                    'days_in_month' => $date->daysInMonth,
                    'test_day'      => 1,
                    'test_year'     => 2016,
                ]);
        }
    }

    public static function validateMonth(int $month)
    {
        if (($month < 1) or ($month > 12))
        {
            throw new BadRequestValidationFailureException(
                'Invalid month passed for anchor',
                null,
                ['month' => $month]);
        }
    }

    public static function validateDay(int $day)
    {
        if (($day < 1) or ($day > 31))
        {
            throw new BadRequestValidationFailureException(
                'Invalid day passed for anchor',
                null,
                ['day' => $day]);
        }
    }

    protected static function getYearlyAnchorForDate(int $day, int $month)
    {
        self::validateDayAndMonth($day, $month);

        return (($month * 100) + $day);
    }
}
