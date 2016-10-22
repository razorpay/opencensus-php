<?php

namespace RZP\Tests\Unit\Models\Schedule;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Schedule;
use Carbon\Carbon;

class LibraryTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/LibraryTestData.php';

        parent::setUp();
    }

    public function testBasicT3Schedule()
    {
        $data = $this->testData[__FUNCTION__];

        $basicT3Schedule = (new Schedule\Repository)->getByIdAndOwnerId('schd_basic_t3', '100000Razorpay');

        $this->runCaseWiseScheduleTest($basicT3Schedule, $data);
    }

    public function testTwoHourSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $twoHourSchedule = (new Schedule\Repository)->getByIdAndOwnerId('schd_2_hourly', '100000Razorpay');

        $this->runCaseWiseScheduleTest($twoHourSchedule, $data);
    }

    public function testEveryTuesdaySchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $tuesdaySchedule = (new Schedule\Repository)->getByIdAndOwnerId('schd_tuesdays', '100000Razorpay');

        $this->runCaseWiseScheduleTest($tuesdaySchedule, $data);
    }

    private function runCaseWiseScheduleTest($schedule, $data)
    {
        foreach ($data as $case) {
            $nextTime = Schedule\Library::getNextApplicableTimeFromSchedule($case['initialTime'], $schedule);

            $this->assertEquals($case['expectedNextTime'], $nextTime);
        }
    }
}
