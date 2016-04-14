<?php

namespace Models\Settlement;

use Carbon\Carbon;

class Holidays
{
    public static $holidays = array(
        [2,  10, 2015],
        [22, 10, 2015],
        [11, 11, 2015],
        [12, 11, 2015],
        [25, 11, 2015],
        [24, 12, 2015],
        [25, 12, 2015],
        [26,  1, 2016],
        [19,  2, 2016],
        [7,   3, 2016],
        [24,  3, 2016],
        [25,  3, 2016],
        [1,   4, 2016], // On Account of Annual Closing of Bank Accounts
        [8,   4, 2016],
        [14,  4, 2016],
        [15,  4, 2016],
        [19,  4, 2016],
        //[1,   5, 2016], // Sunday
        [21,  5, 2016], // working saturday
        [6,   7, 2016],
        [15,  8, 2016],
        [17,  8, 2016],
        [5,   9, 2016],
        [13,  9, 2016],
        // [2,  10, 2016], // Sunday
        [11, 10, 2016],
        [12, 10, 2016],
        // [30, 10, 2016], // Sunday
        [31, 10, 2016],
        [14, 11, 2016],
        [12, 12, 2016],
        // [25, 12, 2016], // Sunday
    );

    public static function isDayHoliday($dayString = 'today', $mode = 'test', $forceResultInTest = false)
    {
        if ($mode === 'test')
        {
            return $forceResultInTest;
        }

        $date = Carbon::parse($dayString,'Asia/Kolkata');

        $flag = false;

        foreach (self::$holidays as list($day, $month, $year))
        {
            if (($date->day === $day) and
                ($date->month === $month) and
                ($date->year === $year))
            {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * Given a carbon day instance,
     * returns whether that saturday was working or not
     * Bank logic: Every non even week of the month is a working saturday
     * @param Carbon\Carbon $day Any Carbon Day
     * return boolean;
     */
    public static function isWorkingSaturday($day)
    {
        assert($day->dayOfWeek === Carbon::SATURDAY);

        return ($day->weekOfMonth % 2 !== 0);
    }

}
