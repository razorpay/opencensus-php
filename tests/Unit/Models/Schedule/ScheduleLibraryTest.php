<?php

namespace RZP\Tests\Unit\Models\Schedule;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Schedule;
use Carbon\Carbon;
use RZP\Constants\Timezone;

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

    private function runCaseWiseScheduleTest($schedule, $cases)
    {
        foreach ($cases as $case)
        {
            $initialTimestamp = $this->getTimestampFromFormatted($case['initialTime']);

            $nextRun = $this->getInitialNextRun($case['initialTime']);

            $nextTime = Schedule\Library::getNextApplicableTime($initialTimestamp, $schedule, $nextRun);

            $calculatedTime = $this->getFormattedTimeFromTimestamp($nextTime);

            $this->assertEquals($case['expectedNextTime'], $calculatedTime);
        }
    }

    private function getTimestampFromFormatted($formattedTime)
    {
        return $this->getTimeObjectFromFormatted($formattedTime)->getTimestamp();
    }

    private function getInitialNextRun($formattedTime)
    {
        $timeObject = $this->getTimeObjectFromFormatted($formattedTime);

        return $timeObject->hour(0)->minute(0)->second(0)->getTimestamp();
    }

    private function getFormattedTimeFromTimestamp($timestamp)
    {
        return $this->getTimeObjectFromTimestamp($timestamp)->format('Y-m-d H:i:s');
    }

    private function getTimeObjectFromFormatted($formattedTime)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $formattedTime, Timezone::IST);
    }

    private function getTimeObjectFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST);
    }
}
