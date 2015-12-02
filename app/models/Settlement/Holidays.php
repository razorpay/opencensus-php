<?php

namespace Models\Settlement;

use Carbon\Carbon;

class Holidays
{
    public static $holidays = array(
        [2,  10],
        [22, 10],
        [11, 11],
        [12, 11],
        [25, 11],
        [24, 12],
        [25, 12],
    );

    public static function isTodayHoliday($mode)
    {
        if ($mode === 'test')
        {
            return false;
        }

        $date = Carbon::today('Asia/Kolkata');

        $flag = false;

        foreach (self::$holidays as list($day, $month))
        {
            if (($date->day === $day) and
                ($date->month === $month))
            {
                $flag = true;
                break;
            }
        }

        return $flag;
    }
}