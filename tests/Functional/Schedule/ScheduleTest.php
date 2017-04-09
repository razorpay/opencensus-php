<?php

namespace RZP\Tests\Functional\Schedule;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;

class ScheduleTest extends TestCase
{
    use ScheduleTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ScheduleTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateSchedule()
    {
        $schedule = $this->createSchedule();

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $schedule);
    }

    public function testEditSchedule()
    {
        $schedule = $this->createSchedule();

        $request = $this->testData[__FUNCTION__];

        $request['url'] = $request['url'] . $schedule['id'];

        $response = $this->makeRequestAndGetContent($request);
    }

    public function testScheduleDefaultAnchor()
    {
        $input = $this->getDefaultScheduleArray();

        unset($input['anchor']);

        $response = $this->createSchedule($input);

        $this->assertEquals(Carbon::MONDAY, $response['anchor']);
    }

    public function testScheduleInvalidPeriod()
    {
        $input = $this->getDefaultScheduleArray();

        $input['period'] = 'invalidPeriod';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input)
        {
            $this->createSchedule($input);
        });
    }

    public function testScheduleInvalidWeeklyAnchor()
    {
        $input = $this->getDefaultScheduleArray();

        $input['anchor'] = Carbon::SATURDAY;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input) {
            $this->createSchedule($input);
        });
    }

    public function testScheduleInvalidType()
    {
        $input = $this->getDefaultScheduleArray();

        $input['type'] = 'not_settlement';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input) {
            $this->createSchedule($input);
        });
    }

    public function testScheduleInvalidHour()
    {
        $input = $this->testData['timedScheduleBody'];

        $input['period'] = 'hourly';
        $input['delay'] = 0;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input)
        {
            $this->createSchedule($input);
        });
    }

    public function testSettledAtTimestampForTimedMerchant()
    {
        $input = $this->testData['timedScheduleBody'];

        // Create and assign timed schedule having hour set to 5
        $response = $this->createAndAssignSchedule($input);

        $data = ['amount' => 100];

        $payment = $this->fixtures->create('payment:captured', $data);

        $txn = $this->getLastTransaction(true);

        $time = Carbon::createFromTimestamp($txn['settled_at'], 'Asia/Kolkata');

        // Check if time is set to hour value in schedule
        $this->assertEquals(12, $time->hour);
    }

    public function testGetSchedule()
    {
        $schedule = $this->createSchedule();

        $this->ba->appAuth();

        $response = $this->fetchSchedule($schedule['id']);

        $this->ba->adminAuth();

        $this->assertArraySelectiveEquals($schedule, $response);
    }

    public function testDeleteSchedule()
    {
        $schedule = $this->createSchedule();

        $this->deleteSchedule($schedule['id']);
    }

    public function testDeleteScheduleInUse()
    {
        $schedule = $this->createAndAssignSchedule();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($schedule)
        {
            $this->ba->adminAuth();
            $this->deleteSchedule($schedule['id']);
        });

        // Assign a new schedule so the original one becomes unused
        $this->createAndAssignSchedule();

        $this->deleteSchedule($schedule['id']);
    }

    public function testAssignScheduleById()
    {
        $this->createAndAssignSchedule();
    }
}
