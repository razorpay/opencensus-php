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

    public static $arrayedHolidays = [
        2016 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                19 => 'Chhatrapati Shivaji Maharaj Jayanti',
            ],
            3 => [
                7  => 'Mahashivratri',
                24 => 'Holi (2nd day)/Dhuleti',
                25 => 'Good Friday',
            ],
            4 => [
                1  => 'Annual closing of Accounts',
                8  => 'Gudi Padwa/Ugadi',
                14 => 'Tamil New Year’s Day/Vishu/Bohag Bihu/Bengali New Year’s Day',
                15 => 'Shree Ram Navami',
                19 => 'Mahavir Jayanti',
            ],
            5 => [
                21 => 'Buddha Pournima/Saga Dawa',
            ],
            7 => [
                6  => 'Ramzan Id (Id-ul-Fitr)/Ratha Yatra',
            ],
            8 => [
                15 => 'Independence Day',
                17 => 'Parsi New Year',
            ],
            9 => [
                5  => 'Ganesh Chaturthi',
                13 => 'Bakri Id (Id-ul-Zuha)/First Onam',
            ],
            10 => [
                11 => 'Dussehra (Vijaya Dashmi)/Durga Puja',
                12 => 'Moharram/Durga Puja (Dasain)/Ashoora',
                31 => 'Diwali (Balipratipada)/Deepavali',
            ],
            11 => [
                14 => 'Guru Nanak Jayanti/Kartik Poornima',
            ],
            12 => [
                12 => 'Id-e-Milad/Eid Milad-un-Nabi',
            ],
        ],
    ];

    /**
     * getNextWorkingDay - Given a Carbon Date get the next working date
     * This includes checks for :
     *     :bank holiday
     *     :non working saturday
     *     :sundays
     */

    // Supports banking holidays for 2016 now
    public static function getNextWorkingDay($date)
    {
        $nextDay = $date->copy();

        do
        {
            $nextDay->addDay();
        }
        while(self::isWorkingDay($nextDay) === false);

        return $nextDay;
    }

    /**
     * getNextWorkingDay - Given a Carbon Date get the next working date
     * This includes checks for :
     *     :bank holiday
     *     :non working saturday
     *     :sundays
     *
     *  Supports banking holidays for 2016 now
     */

    public static function isWorkingDay($date)
    {
        if (self::isSpecifiedBankHoliday($date))
        {
            return false;
        }

        if ($date->dayOfWeek === Carbon::SUNDAY)
        {
            return false;
        }

        if ($date->dayOfWeek === Carbon::SATURDAY
            and self::isWorkingSaturday($date) === false)
        {
            return false;
        }

        return true;
    }

    public static function getSpecifiedBankHolidaysBetween($fromDate, $toDate)
    {
        assert($fromDate->lte($toDate));

        $date = $fromDate->copy();

        $holidays = [];

        do
        {
            $date->addDay();

            if (self::isSpecifiedBankHoliday($date) === true)
            {
                $holidays[] = [
                    'date'      => $date->copy(),
                    'reason'    => self::getReasonForBankHoliday($date),
                ];
            }

        }
        while($date->lt($toDate));

        return $holidays;
    }

    public static function isSpecifiedBankHoliday($date)
    {
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        if (isset(self::$arrayedHolidays[$year])
            and isset(self::$arrayedHolidays[$year][$month])
            and isset(self::$arrayedHolidays[$year][$month][$day]))
        {
            return true;
        }

        return false;
    }

    /** [getReasonForBankHoliday Private function to get reason for a holiday] */
    protected static function getReasonForBankHoliday($date)
    {
        return self::$arrayedHolidays[$date->year][$date->month][$date->day];
    }

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
