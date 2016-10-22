<?php


return [
    'testBasicT3Schedule' => [
        'cases' => [
            //Initial is on Friday, 2 days delay, so expected is Monday 12am
            ['initialTime' => 1477063679, 'expectedNextTime' => 1477247400],
        ],
        'schedule' => [
            'name'       => 'Basic T3',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'daily',
            'interval'   => 1,
            'anchor'     => null,
            'delay'      => 172800,
            'next_run'   => 1451586600,
        ],
    ],

    'testTwoHourSchedule' => [
        'cases' => [
            //Initial at 8.57pm. Delay one hour, so expected is 10pm
            ['initialTime' => 1477063679, 'expectedNextTime' => 1477067400],

            //Initial at 9.01pm. Delay one hour, so expected is 12am
            ['initialTime' => 1477063879, 'expectedNextTime' => 1477074600],
        ],
        'schedule' => [
            'name'       => 'Every 2 hours',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'hourly',
            'interval'   => 2,
            'anchor'     => null,
            'delay'      => 3600,
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
        ],
        'schedule' => [
            'name'       => 'Every Tuesday',
            'owner_id'   => '100000Razorpay',
            'type'       => 'settlement',
            'period'     => 'weekly',
            'interval'   => 1,
            'anchor'     => 2,
            'delay'      => 86400,
            'next_run'   => 1451932200,
        ],
    ],
];
