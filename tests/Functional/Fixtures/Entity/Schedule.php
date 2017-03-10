<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Schedule extends Base
{
    public function setUp()
    {
        $this->fixtures->create('schedule:schedules');
    }

    protected $schedules = [
        [
            'id'          => '100000schedule',
            'name'        => 'Basic T3',
            'type'        => 'settlement',
            'period'      => 'daily',
            'interval'    => 1,
            'delay'       => 3,
            'hour'        => 5,
            'next_run'    => 1451586600,
        ],
    ];

    public function createSchedules()
    {
        $schedules = [];

        foreach ($this->schedules as $schedule)
        {
            $schedules[] = $this->fixtures->create('schedule', $schedule);
        }

        return $schedules;
    }

}
