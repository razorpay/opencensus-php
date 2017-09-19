<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class Holidays
{
    const HOLIDAY_MESSAGE = ['message' => 'Today is a holiday! Happy holidays :)'];

    // Dont't add sundays or non working saturdays as part of this.
    // These refer to settlement holidays only.
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
                9  => 'Unscheduled Bank Holiday',
                14 => 'Guru Nanak Jayanti/Kartik Poornima',
            ],
            12 => [
                12 => 'Id-e-Milad/Eid Milad-un-Nabi',
            ],
        ],
        2017 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                21 => 'BMC Elections 2017',
                24 => 'Mahashivratri',
            ],
            3 => [
                13 => 'Holi (2nd day)/Yaosang 2nd Day',
                28 => 'Gudi Padwa',
            ],
            4 => [
                1  => 'Annual closing of Accounts',
                4  => 'Shree Ram Navami',
                14 => 'Dr. Babasaheb Ambedkar Jayanti/Cheiraoba/Good Friday/Biju Festival',
            ],
            5 => [
                1  => 'Maharashtra Din/May Day',
                10 => 'Buddha Pournima',
            ],
            6 => [
                26 => 'Ramzan Id (Id-ul-Fitr)',
            ],
            7 => [
            ],
            8 => [
                15 => 'Independence Day/Janmashtami',
                17 => 'Parsi New Year (Shahenshahi)',
                25 => 'Ganesh Chaturthi',
            ],
            9 => [
                2  => 'Bakri Id (Id-ul-Zuha)',
                30 => 'Durga Puja/Dussehra (Vijaya Dashmi)',
            ],
            10 => [
                2  => 'Mahatma Gandhi Jayanti',
                19 => 'Diwali Amavasaya (Laxmi Pujan)/Kali Puja',
                20 => 'Diwali (Balipratipada)',
            ],
            11 => [
                4  => 'Guru Nanak Jayanti',
            ],
            12 => [
                1  => 'Id-e-Milad/Eid Milad-un-Nabi',
                25 => 'Christmas',
            ],
        ],
    ];

    /**
     * getNextWorkingDay, getNthWorkingDayFrom
     * Given a Carbon Date get the next/Nth working date from a given date
     *
     * This includes checks for bank holidays, non working saturday, sundays
     *
     * @param Carbon $date input date
     * @param bool   $ignoreBankHolidays
     *
     * @return Carbon $date Next working date
     */
    public static function getNextWorkingDay($date, $ignoreBankHolidays = false): Carbon
    {
        $countDays = 1;

        return self::getNthWorkingDayFrom($date, $countDays, $ignoreBankHolidays);
    }

    public static function getPreviousWorkingDay($date): Carbon
    {
        $prevDay = $date->copy()->subDay();

        while (self::isWorkingDay($prevDay) === false)
        {
            $prevDay->subDay();
        }

        return $prevDay;
    }

    public static function getNthWorkingDayFrom(
                                            $date,
                                            $countDays,
                                            $ignoreBankHolidays = false): Carbon
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
     * @param Carbon $date
     * @param bool   $ignoreBankHolidays
     *
     * @return bool
     */
    public static function isWorkingDay($date, $ignoreBankHolidays = false): bool
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
     * @param Carbon $fromDate
     * @param Carbon $toDate
     *
     * @return array $holidays - All holidays between days
     */
    public static function getSpecifiedBankHolidaysBetween($fromDate, $toDate): array
    {
        // fromDate should be less than or equal to (lte) than toDate
        assertTrue($fromDate->lte($toDate));

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

    public static function isSpecifiedBankHoliday($date): bool
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
     * @param  Carbon $date
     * @return String
     */
    protected static function getReasonForBankHoliday($date): string
    {
        return self::$holidays[$date->year][$date->month][$date->day];
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

    /**
     * Get next settlement holiday after the given day.
     * The below is not an O(n^3) loop.
     * It breaks at the first sight of return.
     * And it is intended to skip most elements.
     *
     * @param Carbon $date
     * @return Carbon $date
     */
    public static function getNextSettlementHoliday($date): Carbon
    {
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        foreach (self::$holidays as $holidayYear => $holidaysInYear)
        {
            // Compare only based on holidayYear.
            $compareDate = self::getDateToCompareWith($holidayYear, $month, $day);

            if ($compareDate->lt($date))
            {
                continue;
            }

            foreach ($holidaysInYear as $holidayMonth => $holidayInMonth)
            {
                // Compare only based on holidayYear and holidayMonth
                $compareDate = self::getDateToCompareWith($holidayYear, $holidayMonth, $day);

                if ($compareDate->lt($date))
                {
                    continue;
                }

                foreach ($holidayInMonth as $holidayDay => $holidayReason)
                {
                    // Compare based on holidayYear, holidayMonth and holidayDay
                    $compareDate = self::getDateToCompareWith($holidayYear, $holidayMonth, $holidayDay);

                    if ($compareDate->lt($date))
                    {
                        continue;
                    }

                    // Only the day the has a date with (holidayYear, holidayMonth and holidayDay)
                    // greater than the current date will be returned.
                    return $compareDate;
                }
            }
        }
    }

    protected static function getDateToCompareWith($year, $month, $date): Carbon
    {
        return Carbon::now(Timezone::IST)->setDate($year, $month, $date)
                                          ->hour(0)
                                          ->minute(0)
                                          ->second(0);
    }
}
