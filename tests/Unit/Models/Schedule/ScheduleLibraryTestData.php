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
            'name'       => 'Basic T3',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'daily',
            'interval'   => 1,
            'anchor'     => null,
            'delay'      => 3,
            'next_run'   => 1451586600,
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
            'name'       => 'Every 2 hours',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'hourly',
            'interval'   => 2,
            'anchor'     => null,
            'delay'      => 0,
            'next_run'   => 1451586600,
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
            'name'       => 'Every Tuesday',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'weekly',
            'interval'   => 1,
            'anchor'     => 2,
            'delay'      => 1,
            'next_run'   => 1451932200,
        ],
    ],
];
