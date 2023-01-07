<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Error\ErrorCode;
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
                // 1  => 'Id-e-Milad/Eid Milad-un-Nabi',
                25 => 'Christmas',
            ],
        ],

        2018 => [
            1 => [
                26 => 'Republic Day',
            ],
            3 => [
                30 => 'Good Friday',
            ],
            6 => [
                16 => 'Id-Ul-Fitr',
            ],
            8 => [
                15 => 'Independece Day',
                22 => 'Bakri ID (ld- UI-Zuha)',
            ],
            10 => [
                2 => 'Mahatma Gandhi Jayanti',
            ],
            11 => [
                21 => 'Id-e-milad',
                23 => 'Guru Nanak Jayanti'
            ],
            12 => [
                25 => 'Christmas',
            ],
        ],

        2019 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                19 => 'Shivaji Jayanti',
            ],
            3 => [
                4  => 'Maha Shivratri',
                21 => 'Holi',
            ],
            4 => [
                1  => 'Annual closing of Banks',
                6  => 'Ugadi/ Gudi Padwa',
                17 => 'Mahavir jayanti',
                19 => 'Good Friday',
                29 => 'Election Day in Mumbai',
            ],
            5 => [
                1  => 'May Day/ Mazdoor Diwas',
                18 => 'Buddha Purnima',
            ],
            6 => [
                5 => 'Ramzan Id (Id-Ul-Fitr)',
            ],
            8 => [
                12 => 'Bakri Id(ld-UI-Zuha)',
                15 => 'Independence Day',
                17 => 'Parsi New Year',
            ],
            9 => [
                2  => 'Ganesh Chaturthi',
                10 => 'Muharram',
            ],
            10 => [
                2  => 'Mahatma Gandhi Jayanti',
                8  => 'Dussehra / Vijaya Dasami',
                21 => 'Election Day in Mumbai',
                28 => 'Diwali',
            ],
            11 => [
                12 => 'Guru Nanak Jayanti',
            ],
            12 => [
                25 => 'Christmas',
            ],
        ],

        2020 => [
            2  => [
                19 => 'Chhatrapati Shivaji Maharaj Jayanti',
                21 => 'Mahashivratri',
            ],
            3  => [
                10 => 'Holi',
                25 => 'Gudhi Padwa',
            ],
            4  => [
                1  => 'Annual closing of banks',
                2  => 'Ram Navami',
                6  => 'Mahavir Jayanti',
                10 => 'Good Friday',
                14 => 'Dr. Babasaheb Ambedkar Jayanti/Bengali New Year’s Day',
            ],
            5  => [
                1  => 'Maharashtra Din/May Day (Labour Day)',
                7  => 'Buddha Pournima',
                25 => 'Ramzan Id (Id-Ul-Fitr) (Shawal-1)',
            ],
            8  => [
                1  => 'Bakri ID (Id-Ul-Zuha)',
                15 => 'Independence Day',
                22 => 'Ganesh Chaturthi',
            ],
            10 => [
                2  => 'Mahatma Gandhi Jayanti',
                30 => 'Id-E-Milad (Milad-un-Nabi)/Baravafat/Lakshmi Puja',
            ],
            11 => [
                14 => 'Diwali Amavasaya (Laxmi Pujan)/Kali Puja',
                16 => 'Diwali (Balipratipada)/Bhaidooj/Chitragupt Jayanti',
                30 => 'Guru Nanak Jayanti/Kartika Purnima',
            ],
            12 => [
                25 => 'Christmas',
            ],
        ],

        2021 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                19 => 'Chhatrapati Shivaji Maharaj Jayanti',
            ],
            3 => [
                11 => 'Mahashivratri',
                29 => 'Holi (Second Day)',
            ],
            4 => [
                1  => 'Annual closing of banks',
                2  => 'Good Friday',
                13 => 'Gudhi Padwa/Telugu New Year’s Day',
                14 => 'Dr. Babasaheb Ambedkar Jayanti/Bengali New Year’s Day',
                21 => 'Ram Navami',
            ],
            5 => [
                1  => 'Maharashtra Din/May Day (Labour Day)',
                13 => 'Ramzan Id (Id-Ul-Fitr) (Shawal-1)',
                26 => 'Buddha Pournima',
            ],
            7 => [
                21 => 'Bakri ID (Id-Ul-Zuha)',
            ],
            8 => [
                16 => 'Parse New Year (Shahenshahi)',
                19 => 'Muharram (Ashoora)',
            ],
            9 => [
                10 => 'Ganesh Chaturthi/Samvatsari (Chaturthi Paksha)/Vinayakar Chathurthi',
            ],
            10 => [
                2  => 'Mahatma Gandhi Jayanti',
                15 => 'Dasara/Dusshera (Vijaya Dashmi)',
                19 => 'Id-E-Milad/Eid-e-Miladunnabi/Milad-i-Sherif(Prophet Mohammad’s Birthday)'
            ],
            11 => [
                4  => 'Diwali Amavasaya (Laxmi Pujan)/Deepavali',
                5  => 'Diwali (Bali Pratipada)/Vikram Samvant New Year Day',
                19 => 'Guru Nanak jayanti',
            ],
            12 => [
                25 => 'Christmas',
            ],
        ],

        2022 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                19 => 'Chhatrapati Shivaji Maharaj Jayanti',
            ],
            3 => [
                1  => 'Mahashivratri',
                18 => 'Holi (Second Day)',
            ],
            4 => [
                1  => 'Annual closing of banks',
                2  => 'Gudhi Padwa/Telugu New Year’s Day',
                14 => 'Dr. Babasaheb Ambedkar Jayanti/Bengali New Year’s Day',
                15 => 'Good Friday/Bengali New Year’s Day (Nababarsha)/Himachal Day/Vishu',
            ],
            5 => [
                3  => 'Bhagvan Shree Parshuram Jayanti/Ramjan-Eid (Eid-UI-Fitra)/Basava Jayanti/Akshaya Tritiya',
                16 => 'Buddha Pournima',
            ],
            8 => [
                9  => 'Muharram (Ashoora)',
                15 => 'Independence Day',
                16 => 'Parsi New Year (Shahenshahi)',
                31 => 'Samvatsari (Chaturthi Paksha)/Ganesh Chaturthi/Varasiddhi Vinayaka Vrata/Vinayakar Chathurthi',
            ],
            10 => [
                5  => 'Dasara/Dusshera (Vijaya Dashmi)',
                24 => 'Kali Puja/Deepavali/Diwali (Laxmi Pujan)/Naraka Chaturdashi',
                26 => 'Govardhan Pooja/Vikram Samvant New Year Day/Bhai Bij/Bhai Duj/Diwali (Bali Pratipada)/Laxmi Puja',
            ],
            11 => [
                8 => 'Guru Nanak jayanti',
            ],
        ],

        2023 => [
            1 => [
                26 => 'Republic Day',
            ],
            2 => [
                18 => 'Mahashivratri (Maha Vad-14)/Sivarathri',
            ],
            3 => [
                7 => 'Holi/Holi (Second Day)/Holika Dahan/Dhulandi/Dol Jatra',
                22 => 'Gudi Padwa/Ugadi Festival/Bihar Divas/Sajibu Nongmapanba (Cheiraoba)',
                30 => 'Shree Ram Navami (Chaite Dashain)',
            ],
            4 => [
                1 => 'Annual closing of banks',
                4 => 'Mahavir Jayanti',
                7 => 'Good Friday',
                14 => 'Dr. Babasaheb Ambedkar Jayanti/Bohag Bihu/Cheiraoba',
                22 => 'Ramzan Eid (Eid-Ul-Fitr)',
            ],
            5 => [
                1 => 'Maharashtra Day/May Day',
                5 => 'Buddha Purnima',
            ],
            6 => [
                28 => 'Bakri Eid (Eid-Ul-Zuha)',
            ],
            7 => [
                29 => 'Muharram (Tajiya)',
            ],
            8 => [
                15 => 'Independence Day',
                16 => 'Parsi New Year (Shahenshahi)',
            ],
            9 => [
                19 => 'Ganesh Chaturthi/Samvatsari (Chaturthi Paksha)',
                28 => 'Eid-E-Milad/Eid-e-Meeladunnabi - (Prophet Mohammad’s Birthday) (Bara Vafat)',
            ],
            10 => [
                2 => 'Mahatma Gandhi Jayanti',
                24 => 'Dussehra/Dusshera (Vijaya Dashmi)/Durga Puja',
            ],
            11 => [
                14 => 'Diwali (Bali Pratipada)/Deepavali/Vikram Samvant New Year Day/Laxmi Puja',
                27 => 'Guru Nanak Jayanti/Karthika Purnima',
            ],
            12 => [
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

            if (self::isWorkingDay($workingDay, $ignoreBankHolidays) === true)
            {
                $countDays--;
            }
        }

        return $workingDay;
    }

    /**
     * Gives the timestamp of working day after adding the offset
     * It'll add the offset to the timestamp passed and
     * if there any holidays in between then the those many days will be added
     *
     * @param int $timestamp
     * @param int $hour
     * @param int $minute
     *
     * @return int
     */
    public static function addOffsetedWorkingTime(int $timestamp, int $hour = 0, int $minute = 0): int
    {
        $time = Carbon::createFromTimestamp($timestamp, Timezone::IST);

        $finalTimestamp = $time->copy()
            ->addHour($hour)
            ->addMinute($minute);

        $diff = $time->diffInDays($finalTimestamp);

        while ($diff > 0)
        {
            $time->addDay();

            if(!self::isWorkingDay($time) === true)
            {
                $finalTimestamp->addDay();

                $diff--;
            }
        }

        return $finalTimestamp->getTimestamp();
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

        return (self::isWeekend($date) === false);
    }

    /**
     * returns true if the the given date falls under banks nonworking weekend
     * which includes sundays and non working saturdays
     *
     * @param Carbon $date
     *
     * @return bool
     */
    public static function isWeekend(Carbon $date): bool
    {
        if ($date->dayOfWeek === Carbon::SUNDAY)
        {
            return true;
        }

        // If it's a saturday, then check if it's a working saturday
        if (($date->dayOfWeek === Carbon::SATURDAY) and
            (self::isWorkingSaturday($date) === false))
        {
            return true;
        }

        return false;
    }

    /**
     * give the list of days which are bank non working weekends between given dates
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public static function getListOfWeekendsBetweenDates(Carbon $start, Carbon $end): array
    {
        $listOfWeekends = [];

        //
        // diff in days also consider time and it wont return the right result
        // so, we are resetting the dates to start and end time
        //
        $start->startOfDay();

        $end->endOfDay();

        $days = $start->diffInDays($end);

        $date = $start->copy();

        while ($days !== 0)
        {
            if (self::isWeekend($date) === true)
            {
                $listOfWeekends[] = $date->copy();
            }

            $date->addDay();

            $days--;
        }

        return $listOfWeekends;
    }

    /**
     * give the list of days which are bank holidays between given dates
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public static function getListOfHolidaysBetweenDates(Carbon $start, Carbon $end): array
    {
        $listOfHolidays = [];

        //
        // diff in days also consider time and it wont return the right result
        // so, we are resetting the dates to start and end time
        //
        $start->startOfDay();

        $end->endOfDay();

        $days = $start->diffInDays($end);

        $date = $start->copy();

        while ($days !== 0)
        {
            if (self::isSpecifiedBankHoliday($date) === true)
            {
                $listOfHolidays[] = $date->copy();
            }

            $date->addDay();

            $days--;
        }

        return $listOfHolidays;
    }

    /**
     * constructs the holidays detail message given the settlement time
     * this will find all the holidays between current timestamp and settlement time
     * then construct the message will all the details acquired
     *
     * @param Carbon $settlementTime
     *
     * @return string|null
     */
    public static function constructDetailsMessage(Carbon $settlementTime)
    {
        $response = '';

        $currentTimestamp = Carbon::now(Timezone::IST);

        $weekendList = self::getListOfWeekendsBetweenDates($currentTimestamp, $settlementTime);

        if (empty($weekendList) === false)
        {
            $response .= (count($weekendList) > 2) ?
                'Saturday and Sunday are non-working days' :
                'Sunday is a non-working day';
        }

        $holidayList = self::getListOfHolidaysBetweenDates($currentTimestamp, $settlementTime);

        if (empty($holidayList) === false)
        {
            $holidayReasons = array_map(function($date)
            {
                return self::getReasonForBankHoliday($date);
            }, $holidayList);

            $holidayDateFormatted = array_map(function($date)
            {
                return $date->format('M d');
            }, $holidayList);

            $response .= ((empty($response) === true) ? 'Bank' : ' and bank')
                        . ' holiday [' . implode(',', $holidayReasons)
                        . '] on ' . implode(',', $holidayDateFormatted);
        }

        if (empty($response) === true)
        {
            return null;
        }

        $response .= '. So, the next settlement will happen on '
                     . $settlementTime->format('M d, hA');

        return $response;
    }

    /**
     * getSpecifiedBankHolidaysBetween - fromDate and toDate
     *
     * @param Carbon $fromDate
     * @param Carbon $toDate
     *
     * @return array $holidays - All holidays between days
     * @throws Exception\AssertionException
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

    /**
     * given the year it will return the list of holidays
     * if the holiday list is not available then it'll throw an exception
     *
     * @param int $year
     * @return array
     * @throws Exception\BadRequestException
     */
    public static function getHolidayListForYear(int $year): array
    {
        if (isset(self::$holidays[$year]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $holidayDetails = [];

        foreach (self::$holidays[$year] as $month => $holiday)
        {
            foreach ($holiday as $day => $description)
            {
                $date = Carbon::createFromDate($year, $month, $day, Timezone::IST);

                $holidayDetails[] = [
                    'date'        => $date->format('d/m/Y'),
                    'description' => $description,
                ];
            }
        }

        return [
            $year => $holidayDetails
        ];
    }
}
