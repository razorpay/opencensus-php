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
            $initialTime = $this->getTimeStamp($case['initialTime']);

            $nextTime = Schedule\Library::getNextApplicableTime($initialTime, $schedule);

            $calculatedTime = $this->getFormattedTime($nextTime);

            $this->assertEquals($calculatedTime, $case['expectedNextTime']);
        }
    }

    private function getTimeStamp($dateTime)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s',
                                        $dateTime,
                                        'Asia/Kolkata')->timestamp;
    }

    private function getFormattedTime($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')
                                            ->format('Y-m-d H:i:s');
    }
}
