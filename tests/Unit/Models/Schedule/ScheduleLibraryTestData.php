<?php


return [
    'testBasicT3Schedule' => [
        'cases' => [
            //Initial is on Friday, Sat-Sun are holidays, 2 days delay
            //is Monday and Tuesday. Expected time is Wednesday.
            ['initialTime' => 1477063679, 'expectedNextTime' => 1477420200],

            //Initial is 13th April. 14th and 15th are Holidays,
            //16th is a Saturday, but a working day. 17th is a Sunday.
            //So 2 day delay is Saturday and Monday. Expected time is Tuesday.
            ['initialTime' => 1460545030, 'expectedNextTime' => 1461004200],
        ],
        'schedule' => [
            'name'       => 'Basic T3',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'daily',
            'interval'   => 1,
            'anchor'     => null,
            'delay'      => 2,
            'next_run'   => 1451586600,
        ],
    ],

    'testTwoHourSchedule' => [
        'cases' => [
            //Initial at 8.57pm. Delay one hour, so expected is 10pm
            ['initialTime' => 1477063679, 'expectedNextTime' => 1477067400],

            //Initial at 9.01pm. Delay one hour, so expected is 12am
            ['initialTime' => 1477063879, 'expectedNextTime' => 1477074600],

            //Initial is 11.01pm on a Friday, 21st October. Delay one hour,
            //but next 2 days are weekend holidays. Expected time is Monday.
            ['initialTime' => 1477071079, 'expectedNextTime' => 1477247400],
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
            ['initialTime' => 1477063679, 'expectedNextTime' => 1477333800],

            //Initial at Monday 12.01am, i.e. just past midnight.
            //Delay one day, so expected is Tuesday next week
            ['initialTime' => 1477248400, 'expectedNextTime' => 1477938600],

            //Initial time is 8th October. Next schedule day is Tuesday,
            //11th October, but both 11th and 12th October are holidays.
            //So expected time is 13th, Thursday.
            ['initialTime' => 1475925622, 'expectedNextTime' => 1476297000],
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
