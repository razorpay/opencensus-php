<?php

namespace RZP\Tests\Unit\Models\Schedule;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Schedule;
use Carbon\Carbon;

class ScheduleLibraryTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ScheduleLibraryTestData.php';

        parent::setUp();
    }

    public function testBasicT3Schedule()
    {
        $data = $this->testData[__FUNCTION__];

        $basicT3Schedule = new Schedule\Entity($data['schedule']);

        $this->runCaseWiseScheduleTest($basicT3Schedule, $data['cases']);
    }

    public function testTwoHourSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $twoHourSchedule = new Schedule\Entity($data['schedule']);

        $this->runCaseWiseScheduleTest($twoHourSchedule, $data['cases']);
    }

    public function testEveryTuesdaySchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $tuesdaySchedule = new Schedule\Entity($data['schedule']);

        $this->runCaseWiseScheduleTest($tuesdaySchedule, $data['cases']);
    }

    private function runCaseWiseScheduleTest($schedule, $cases)
    {
        foreach ($cases as $case)
        {
            $nextTime = Schedule\Library::getNextApplicableTimeFromSchedule($case['initialTime'], $schedule);

            $this->assertEquals($case['expectedNextTime'], $nextTime);
        }
    }
}
