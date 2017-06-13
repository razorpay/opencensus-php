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

        $this->markTestSkipped('Not using next run of schedule now');
    }

    public function testBasicT3Schedule()
    {
        $data = $this->testData[__FUNCTION__];

        $basicT3Schedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($basicT3Schedule, $data['cases']);
    }

    public function testTwoHourSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $twoHourSchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($twoHourSchedule, $data['cases']);
    }

    public function testEveryTuesdaySchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $tuesdaySchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($tuesdaySchedule, $data['cases']);
    }

    public function testEndOfEveryMonthSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $endMonthSchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($endMonthSchedule, $data['cases']);
    }

    public function testTenthOfEveryMonthSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $tenthOfMonthSchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($tenthOfMonthSchedule, $data['cases']);
    }

    public function testSecondWeekOfEveryMonthSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $secondWeekSchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($secondWeekSchedule, $data['cases']);
    }

    public function testLastMondayOfEveryMonthSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $lastWeekSchedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($lastWeekSchedule, $data['cases']);
    }

    public function testTimedSchedule()
    {
        $data = $this->testData[__FUNCTION__];

        $timedSchedule = (new Schedule\Entity)->build($data['schedule']);

        $timedSchedule->updateNextRun();

        $calculatedNextRun = $this->getTimeObject($timedSchedule->getNextRun());

        $this->assertEquals($data['schedule']['hour'], $calculatedNextRun->hour);
    }

    private function runCaseWiseScheduleTest($schedule, $cases)
    {
        foreach ($cases as $case)
        {
            $initialTime = $this->getTimeStamp($case['initialTime']);

            $nextTime = Schedule\Library::getNextApplicableTime($initialTime, $schedule, $schedule->getNextRun());

            $calculatedTime = $this->getFormattedTime($nextTime);

            $this->assertEquals($case['expectedNextTime'], $calculatedTime);
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
        return $this->getTimeObject($timestamp)->format('Y-m-d H:i:s');
    }

    private function getTimeObject($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');
    }
}
