<?php


return [
    'testBasicT3Schedule' => [
        //Initial is on Friday, 2 days delay, so expected is Monday 12am
        ['initialTime' => 1477063679, 'expectedNextTime' => 1477247400],
    ],

    'testTwoHourSchedule' => [
        //Initial at 8.57pm. Delay one hour, so expected is 10pm
        ['initialTime' => 1477063679, 'expectedNextTime' => 1477067400],

        //Initial at 9.01pm. Delay one hour, so expected is 12am
        ['initialTime' => 1477063879, 'expectedNextTime' => 1477074600],
    ],

    'testEveryTuesdaySchedule' => [
        //Initial at Friday. Delay one day, so expected is Tuesday 12am
        ['initialTime' => 1477063679, 'expectedNextTime' => 1477333800],

        //Initial at Monday 12.01am, i.e. just past midnight.
        //Delay one day, so expected is Tuesday next week
        ['initialTime' => 1477248400, 'expectedNextTime' => 1477938600],
    ],
];
