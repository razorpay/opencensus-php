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
    );

    public static function isTodayHoliday($mode)
    {
        if ($mode === 'test')
        {
            return false;
        }

        $date = Carbon::today('Asia/Kolkata');

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
}