<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

class Holidays
{
    public static $holidays = [
        2015 => [
            9 => [
                17  => 'Ganesh Chaturthi',
                25 => 'Bakri Id (Id-ul-Zuha)',
            ],
            10 => [
                2  => 'Gandhi Jayanthi',
                22 => 'Dussehra (Vijaya Dashmi)/Durga Puja',
                24 => 'Moharram/Durga Puja (Dasain)',
            ],
            11 => [
                11 => 'Diwali Amavasya (Laxmi Poojan)',
                12 => 'Diwali (Balipratipada)/Deepavali',
                25 => 'Guru Nanak Jayanti/Kartik Poornima',
            ],
            12 => [
                24 => 'Id-e-Milad/Milad-un-Nabi/Christmas Eve',
                25 => 'Christmas',
            ],
        ],
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
     * getNextWorkingDay, getNthWorkingDayFrom
     * Given a Carbon Date get the next/Nth working date from a given date
     *
     * This includes checks for bank holidays, non working saturday, sundays
     *
     * @param Carbon\Carbon $date input date
     * @return Carbon\Carbon $date Next working date
     */
    public static function getNextWorkingDay($date, $ignoreBankHolidays = false)
    {
        $countDays = 1;

        return self::getNthWorkingDayFrom($date, $countDays, $ignoreBankHolidays);
    }

    public static function getPreviousWorkingDay($date)
    {
        $prevDay = $date->copy()->subDay();

        while (self::isWorkingDay($prevDay) === false)
        {
            $prevDay->subDay();
        }

        return $prevDay;
    }

    public static function getNthWorkingDayFrom($date,
                                                $countDays,
                                                $ignoreBankHolidays = false)
    {
        $workingDay = $date->copy()->hour(0)->minute(0)->second(0);

        while ($countDays > 0)
        {
            $workingDay->addDay();

            if (self::isWorkingDay($workingDay, $ignoreBankHolidays))
            {
                $countDays--;
            }
        }

        return $workingDay;
    }

    /**
     * Check if the given date is a working day or not
     *
     * This includes checks for bank holiday, non working saturday, sundays
     *
     * @param Carbon\Carbon $date
     * @return boolean
     */
    public static function isWorkingDay($date, $ignoreBankHolidays = false)
    {
        if (($ignoreBankHolidays === false) and
            (self::isSpecifiedBankHoliday($date)))
        {
            return false;
        }

        if ($date->dayOfWeek === Carbon::SUNDAY)
        {
            return false;
        }

        // If it's a saturday, then check if it's a working saturday
        if (($date->dayOfWeek === Carbon::SATURDAY) and
            (self::isWorkingSaturday($date) === false))
        {
            return false;
        }

        return true;
    }

    /**
     * getSpecifiedBankHolidaysBetween - fromDate and toDate
     *
     * @param  Carbon\Carbon $fromDate
     * @param  Carbon\Carbon $toDate
     * @return $holidays - All holidays between days
     */
    public static function getSpecifiedBankHolidaysBetween($fromDate, $toDate)
    {
        // fromDate should be less than or equal to (lte) than toDate
        rzpAssert($fromDate->lte($toDate));

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
        while ($date->lt($toDate));

        return $holidays;
    }

    public static function isSpecifiedBankHoliday($date)
    {
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        if ((isset(self::$holidays[$year])) and
            (isset(self::$holidays[$year][$month])) and
            (isset(self::$holidays[$year][$month][$day])))
        {
            return true;
        }

        return false;
    }

    /**
     * Private function to get reason for a holiday
     *
     * @param  Carbon\Carbon $date
     * @return boolean
     */
    protected static function getReasonForBankHoliday($date)
    {
        return self::$holidays[$date->year][$date->month][$date->day];
    }

    /**
     * Given a carbon day instance,
     * Returns whether that saturday was working or not
     * Bank logic: Every non-even week of the month is a working saturday
     *
     * @param Carbon\Carbon $day Any Carbon Day
     * @return boolean
     */
    public static function isWorkingSaturday($day)
    {
        rzpAssert($day->dayOfWeek === Carbon::SATURDAY);

        return ($day->weekOfMonth % 2 !== 0);
    }
}
