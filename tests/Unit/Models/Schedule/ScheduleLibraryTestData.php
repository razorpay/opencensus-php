<?php


return [
    'testBasicT3Schedule' => [
        'cases' => [
            //Initial is on Friday, Sat-Sun are holidays, 3 days delay
            //is Monday, Tuesday, Wednesday. Expected time is Thursday.
            [
                'initialTime' => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-26 00:00:00"
            ],

            //Initial is 13th April. 14th and 15th are Holidays,
            //16th is a Saturday, but a working day. 17th is a Sunday.
            //18th is working, but 19th is another holiday.
            //Expected date is therefore 20th.
            [
                'initialTime' => "2016-04-13 16:27:10",
                'expectedNextTime' => "2016-04-20 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Basic T3',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'daily',
            'interval'    => 1,
            'anchor'      => null,
            'delay'       => 3,
            'next_run'    => 1451586600,
        ],
    ],

    'testTwoHourSchedule' => [
        'cases' => [
            //Initial at 8.57pm. Delay one hour, so expected is 10pm
            [
                'initialTime' => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-21 22:00:00"
            ],

            //Initial at 9.01pm. Delay one hour, so expected is 12am
            [
                'initialTime' => "2016-10-20 21:01:19",
                'expectedNextTime' => "2016-10-21 00:00:00"
            ],

            //Initial is 11.01pm on a Friday, 21st October. Delay one hour,
            //but next 2 days are weekend holidays. Expected time is Monday.
            [
                'initialTime' => "2016-10-21 23:01:19",
                'expectedNextTime' => "2016-10-24 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Every 2 hours',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'hourly',
            'interval'    => 2,
            'anchor'      => null,
            'delay'       => 0,
            'next_run'    => 1451586600,
        ],
    ],

    'testEveryTuesdaySchedule' => [
        'cases' => [
            //Initial at Friday. Delay one day, so expected is Tuesday 12am
            [
                'initialTime' => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-10-25 00:00:00"
            ],

            //Initial at Monday 12.01am, i.e. just past midnight.
            //Delay one day, so expected is Tuesday next week
            [
                'initialTime' => "2016-10-24 00:16:40",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],

            //Initial time is 8th October. Next schedule day is Tuesday,
            //11th October, but both 11th and 12th October are holidays.
            //So expected time is 13th, Thursday.
            [
                'initialTime' => "2016-10-08 16:50:22",
                'expectedNextTime' => "2016-10-13 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Every Tuesday',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'weekly',
            'interval'    => 1,
            'anchor'      => 2,
            'delay'       => 1,
            'next_run'    => 1451932200,
        ],
    ],

    'testEndOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st September. Expected time is end
            //of month, 30th September.
            [
                'initialTime' => "2016-09-21 20:57:59",
                'expectedNextTime' => "2016-09-30 00:00:00"
            ],
            //Initial time is 21st October. End of month is
            //31st October, but 31st is Diwali. So expected
            //time is 1st November.
            [
                'initialTime' => "2016-10-21 20:57:59",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'End of Month',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'monthly-date',
            'interval'    => null,
            'anchor'      => -1,
            'delay'       => 2,
            'next_run'    => null,
        ],
    ],

    'testTenthOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st September. Expected time
            //is 10th of next month.
            [
                'initialTime' => "2016-09-21 20:57:59",
                'expectedNextTime' => "2016-10-10 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => '10th of Month',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'monthly-date',
            'interval'    => null,
            'anchor'      => 10,
            'delay'       => 2,
            'next_run'    => null,
        ],
    ],

    'testSecondWeekOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st October. Second Monday
            //is on the 10th, so expected time is 10th October.
            [
                'initialTime' => "2016-10-01 20:57:59",
                'expectedNextTime' => "2016-10-10 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Second week of Month',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'monthly-week',
            'interval'    => null,
            'anchor'      => 2,
            'delay'       => 2,
            'next_run'    => null,
        ],
    ],

    'testLastMondayOfEveryMonthSchedule' => [
        'cases' => [
            //Initial time is 21st October. Last Monday
            //is on the 31st, on Diwali. So expected time
            //is 1st Novermber.
            [
                'initialTime' => "2016-10-01 20:57:59",
                'expectedNextTime' => "2016-11-01 00:00:00"
            ],
        ],
        'schedule' => [
            'name'        => 'Last week of Month',
            'merchant_id' => '100000Razorpay',
            'type'        => 'settlement',
            'period'      => 'monthly-week',
            'interval'    => null,
            'anchor'      => -1,
            'delay'       => 2,
            'next_run'    => null,
        ],
    ],
];
