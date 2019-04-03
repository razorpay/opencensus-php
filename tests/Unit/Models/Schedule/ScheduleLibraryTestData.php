<?php


return [
    'testBasicT3Schedule' => [
        'cases' => [
            //Initial is on Friday, Sat-Sun are holidays, 3 days delay
            //is Monday, Tuesday, Wednesday. Expected time is Thursday.
            [
                'initialTime'      => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-26 00:00:00"
            ],

            //Initial is 13th April. 14th and 15th are Holidays,
            //16th is a Saturday, but a working day. 17th is a Sunday.
            //18th is working, but 19th is another holiday.
            //Expected date is therefore 20th.
            [
                'initialTime'      => "2016-04-13 16:27:10",
                'expectedNextTime' => "2016-04-20 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Basic T3',
            'period'      => 'daily',
            'interval'    => 1,
            'anchor'      => null,
            'delay'       => 3,
        ],
    ],

    'testBasicT32Schedule' => [
        'cases' => [
            // Simply adding 32 brings us to 22nd June, but skips
            // 7 non-working days. Add those and you reach 1st July.
            [
                'initialTime'      => "2016-05-21 20:57:59",
                'expectedNextTime' => "2016-07-01 00:00:00"
            ],

            // Calculation similar to the one above brings us to 24th
            // May, plus 3 holidays in late April to reach 27th May
            [
                'initialTime'      => "2016-04-13 16:27:10",
                'expectedNextTime' => "2016-05-27 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Basic T32',
            'period'      => 'daily',
            'interval'    => 1,
            'anchor'      => null,
            'delay'       => 32,
        ],
    ],

    'testT3ScheduleWithMinTime' => [
        'cases' => [
            // This will check the case when settled at time
            // will be 29 jan and ref time 20 jan next run will
            // be equal to 29 jan only
            [
                'initialTime'      => "2018-01-20 00:00:00",
                'expectedNextTime' => "2018-01-29 00:00:00"
            ],
        ],

        // Delay 5 will gives us settled at as 2018-01-29 00:00:00
        'schedule' => [
            'name'        => 'Basic T3',
            'period'      => 'daily',
            'interval'    => 3,
            'anchor'      => null,
            'delay'       => 5,
        ],

    ],

    'testComputeFutureRun' => [
        'cases' => [
            // This will check the case when min time is not
            // given and next run is 3 days later which will
            // be 23 jan in this case
            [
                'refTime'          => "2018-01-20 00:00:00",
                'minTime'          => null,
                'expectedNextTime' => "2018-01-23 00:00:00"
            ],
            // This will check the case if minTime is equal to refTime
            // nextrun will be equal to reftime
            [
                'refTime'          => "2018-01-20 00:00:00",
                'minTime'          => "2018-01-20 00:00:00",
                'expectedNextTime' => "2018-01-20 00:00:00"
            ],
        ],

        'schedule' => [
            'name'        => 'Basic T3',
            'period'      => 'daily',
            'interval'    => 3,
            'anchor'      => null,
            'delay'       => 5,
        ],

    ],

    'testTwoHourSchedule' => [
        'cases' => [
            //Initial at 8.57pm. Delay one hour, so expected is 10pm
            [
                'initialTime'      => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-21 22:00:00"
            ],

            //Initial at 9.01pm. Delay one hour, so expected is 12am
            [
                'initialTime'      => "2016-10-20 21:01:19",
                'expectedNextTime' => "2016-10-21 00:00:00"
            ],

            //Initial is 11.01pm on a Friday, 21st October. Delay one hour,
            //but next 2 days are weekend holidays. Expected time is Monday.
            [
                'initialTime'      => "2016-10-21 23:01:19",
                'expectedNextTime' => "2016-10-24 00:00:00"
            ],

            // Initial is 11.01pm on a Friday, 21st October. Delay one hour,
            // but next day is 15th Aug 2018 and is a holidays.
            // Expected time is 17th Aug 2018.
            [
                'initialTime'      => "2018-08-14 23:01:19",
                'expectedNextTime' => "2018-08-16 00:00:00",
            ],

            // 15th is holiday but as the ignoreHolidays flag is set
            [
                'initialTime'      => "2018-08-14 23:01:19",
                'expectedNextTime' => "2018-08-15 02:00:00",
                'ignoreHolidays'   => true,
            ],
        ],
        'schedule' => [
            'name'        => 'Every 2 hours',
            'period'      => 'hourly',
            'interval'    => 2,
            'anchor'      => null,
            'delay'       => 1,
        ],
    ],

    'testEveryTuesdaySchedule' => [
        'cases' => [
            //Initial at Friday. Delay one day, so expected is Tuesday 12am
            [
                'initialTime'      => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-25 00:00:00"
            ],

            //Initial at Monday 12.01am, i.e. just past midnight.
            //Delay one day, so expected is Tuesday next week
            [
                'initialTime'      => "2016-10-24 00:16:40",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],

            //Initial time is 8th October. Next schedule day is Tuesday,
            //11th October, but both 11th and 12th October are holidays.
            //So expected time is 13th, Thursday.
            [
                'initialTime'      => "2016-10-08 16:50:22",
                'expectedNextTime' => "2016-10-13 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Every Tuesday',
            'period'      => 'weekly',
            'interval'    => 1,
            'anchor'      => 2,
            'delay'       => 1,
        ],
    ],

    'testEndOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st September. Expected time is end
            //of month, 30th September.
            [
                'initialTime'      => "2016-09-21 20:57:59",
                'expectedNextTime' => "2016-09-30 00:00:00"
            ],
            //Initial time is 21st October. End of month is
            //31st October, but 31st is Diwali. So expected
            //time is 1st November.
            [
                'initialTime'      => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'End of Month',
            'period'      => 'monthly-date',
            'interval'    => null,
            'anchor'      => -1,
            'delay'       => 2,
        ],
    ],

    'testT0WithHourSchedule' => [
        'cases' => [
            //Initial time is 21st September before 3pm.
            //Expected time is same day at 3pm.
            [
                'initialTime'      => "2016-09-21 10:57:59",
                'expectedNextTime' => "2016-09-21 15:00:00"
            ],
            //Initial time is 21st September after 3pm.
            //Expected time is next day at 3pm.
            [
                'initialTime'      => "2016-09-21 20:57:59",
                'expectedNextTime' => "2016-09-22 15:00:00"
            ],
            //Initial time is 30th October. Next day is Diwali.
            //So expected time is 1st November 3pm.
            [
                'initialTime'      => "2016-10-30 20:57:59",
                'expectedNextTime' => "2016-11-01 15:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'T0-3PM',
            'period'      => 'daily',
            'interval'    => 1,
            'anchor'      => null,
            'delay'       => 0,
            'hour'        => 15,
        ],
    ],

    'testTenthOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st September. Expected time
            //is 10th of next month.
            [
                'initialTime'      => "2016-09-21 20:57:59",
                'expectedNextTime' => "2016-10-10 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => '10th of Month',
            'period'      => 'monthly-date',
            'interval'    => null,
            'anchor'      => 10,
            'delay'       => 2,
        ],
    ],

    'testSecondWeekOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st October. Second Monday
            //is on the 10th, so expected time is 10th October.
            [
                'initialTime'      => "2016-10-01 20:57:59",
                'expectedNextTime' => "2016-10-10 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Second week of Month',
            'period'      => 'monthly-week',
            'interval'    => null,
            'anchor'      => 2,
            'delay'       => 2,
        ],
    ],

    'testLastMondayOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st October. Last Monday
            //is on the 31st, on Diwali. So expected time
            //is 1st Novermber.
            [
                'initialTime'      => "2016-10-01 20:57:59",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Last week of Month',
            'period'      => 'monthly-week',
            'interval'    => null,
            'anchor'      => -1,
            'delay'       => 2,
        ],
    ],

    'testSameDayHouredSchedule' => [
        'cases' => [
            // Initial time is just past 1pm.
            // Expected time is 1pm tomorrow.
            [
                'initialTime'      => "2018-04-11 13:27:00",
                'expectedNextTime' => "2018-04-12 13:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'T0-1PM',
            'period'      => 'daily',
            'interval'    => 1,
            'anchor'      => null,
            'delay'       => 0,
            'hour'        => 13,
        ],
    ],

    'testMinuteSchedule' => [
        'cases' => [
            [
                'initialTime'      => "2019-03-21 14:00:01",
                'expectedNextTime' => "2019-03-21 14:15:00",
                'ignoreHolidays'   => true
            ],
            [
                'initialTime'      => "2019-03-20 23:55:01",
                'expectedNextTime' => "2019-03-21 00:00:00",
                'ignoreHolidays'   => true
            ],
            [
                'initialTime'      => "2018-12-31 23:55:01",
                'expectedNextTime' => "2019-01-01 00:00:00",
                'ignoreHolidays'   => true
            ],
        ],
        'schedule' => [
            'name'        => 'Every 15 minutes',
            'period'      => 'minute',
            'interval'    => 15,
            'anchor'      => null,
            'delay'       => 0,
            'hour'        => 0,
        ],
    ],
];
