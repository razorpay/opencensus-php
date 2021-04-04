<?php

namespace RZP\Tests\Unit\Models\Schedule;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Schedule;
use Carbon\Carbon;
use RZP\Constants\Timezone;

class ScheduleLibraryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/ScheduleLibraryTestData.php';

        parent::setUp();
    }

    public function testBasicT3Schedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testBasicT32Schedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testT3ScheduleWithMinTime()
    {
        $this->startScheduleLibraryTest();
    }

    public function testTwoHourSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testEveryTuesdaySchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testEndOfEveryMonthSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testT0WithHourSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testTenthOfEveryMonthSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testSecondWeekOfEveryMonthSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testLastMondayOfEveryMonthSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testSameDayHouredSchedule()
    {
        $this->startScheduleLibraryTest();
    }

    public function testComputeFutureRun()
    {
        $data = $this->testData[__FUNCTION__];

        $basicT3Schedule = (new Schedule\Entity)->build($data['schedule']);

        $this->computeFutureRun($basicT3Schedule, $data['cases']);
    }

    protected function startScheduleLibraryTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $testName = $trace[1]['function'];

        $data = $this->testData[$testName];

        $schedule = (new Schedule\Entity)->build($data['schedule']);

        $this->runCaseWiseScheduleTest($schedule, $data['cases']);
    }

    private function computeFutureRun($schedule, $cases)
    {
        foreach ($cases as $case)
        {
            $refTime = $this->getTimeObjectFromFormatted($case['refTime']);

            $minTime = null;

            if ($case['minTime'] !== null)
            {
                $minTime = $this->getTimeObjectFromFormatted($case['minTime']);
            }

            $nextTime = Schedule\Library::computeFutureRun($schedule, $refTime, $minTime, $ignoreBankHolidays = true);

            $calculatedTime = $this->getFormattedTimeFromTimestamp($nextTime->timestamp);

            $this->assertEquals($case['expectedNextTime'], $calculatedTime);
        }
    }

    private function runCaseWiseScheduleTest($schedule, $cases)
    {
        foreach ($cases as $case)
        {
            $initialTimestamp = $this->getTimestampFromFormatted($case['initialTime']);

            $nextRun = $this->getInitialNextRun($case['initialTime'], $schedule);

            $ignoreHolidays = $case['ignoreHolidays'] ?? false;

            $nextTime = Schedule\Library::getNextApplicableTime(
                $initialTimestamp, $schedule, $nextRun, $ignoreHolidays);

            $calculatedTime = $this->getFormattedTimeFromTimestamp($nextTime);

            $this->assertEquals($case['expectedNextTime'], $calculatedTime);
        }
    }

    private function getTimestampFromFormatted($formattedTime)
    {
        return $this->getTimeObjectFromFormatted($formattedTime)->getTimestamp();
    }

    private function getInitialNextRun($formattedTime, $schedule)
    {
        $timeObject = $this->getTimeObjectFromFormatted($formattedTime);

        $hour = $schedule->getHour();

        return $timeObject->hour($hour)->minute(0)->second(0)->getTimestamp();
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

    public function testMinuteSchedule()
    {
        $this->startScheduleLibraryTest();
    }
}
