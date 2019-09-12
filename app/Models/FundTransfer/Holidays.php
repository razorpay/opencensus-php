<?php


namespace RZP\Models\FundTransfer;


use Carbon\Carbon;

class Holidays
{

    /**
     * List Contains Only Bank Holidays i.e. doesn't include local holidays etc
     * @var array
     */
    public static $bankHolidays = [
        2019 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                19 => 'Shivaji Jayanti',
            ],
            3 => [
                4 => 'Maha Shivratri',
                21 => 'Holi',
            ],
            4 => [
                1 => 'Annual closing of Banks',
                6 => 'Ugadi/ Gudi Padwa',
                17 => 'Mahavir jayanti',
                19 => 'Good Friday',
                29 => 'Election Day in Mumbai',
            ],
            5 => [
                1 => 'May Day/ Mazdoor Diwas',
                18 => 'Buddha Purnima',
            ],
            6 => [
                5 => 'Ramzan Id (Id-Ul-Fitr)',
            ],
            8 => [
                12 => 'Bakri Id(ld-UI-Zuha)',
                15 => 'Independence Day',
            ],
            10 => [
                2 => 'Mahatma Gandhi Jayanti',
                8 => 'Dussehra / Vijaya Dasami',
            ],
            12 => [
                25 => 'Christmas',
            ],
        ],
    ];


    /**
     * Check if the given date is a working day or not
     *
     * This includes checks for bank holiday, non working saturday, sundays
     *
     * @param Carbon $date
     *
     * @return bool
     */
    public static function isWorkingDay($date): bool
    {
        if (self::isSpecifiedBankHoliday($date) === true) {
            return false;
        }

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }

        // If it's a saturday, then check if it's a working saturday
        if (($date->dayOfWeek === Carbon::SATURDAY) and
            (self::isWorkingSaturday($date) === false)) {
            return false;
        }

        return true;
    }

    public static function isSpecifiedBankHoliday($date): bool
    {
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        if ((isset(self::$bankHolidays[$year])) and
            (isset(self::$bankHolidays[$year][$month])) and
            (isset(self::$bankHolidays[$year][$month][$day])))
        {
            return true;
        }

        return false;
    }

    /**
     * Given a carbon day instance,
     * Returns whether that saturday was working or not
     * Bank logic: Every non-even week of the month is a working saturday
     *
     * @param Carbon $day Any Carbon Day
     * @return boolean
     */
    public static function isWorkingSaturday($day): bool
    {
        assertTrue($day->dayOfWeek === Carbon::SATURDAY);

        return ($day->weekOfMonth % 2 !== 0);
    }
}
