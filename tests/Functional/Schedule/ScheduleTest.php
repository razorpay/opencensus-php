<?php

namespace RZP\Tests\Functional\Schedule;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ScheduleTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ScheduleTestData.php';

        parent::setUp();

        $this->ba->appAuth();

        $this->testScheduleBody = $this->testData['testScheduleBody'];
    }

    public function testCreateSchedule()
    {
        $this->createSchedule();
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
        $request = $this->testData['createSchedule'];

        $request['content'] = $this->testData['timedScheduleBody'];

        $request['content']['period'] = 'hourly';
        $request['content']['delay'] = 0;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request) {
            $this->makeRequestAndGetContent($request);
        });
    }

    public function testSettledAtTimestampForTimedMerchant()
    {
        $input = $this->testData['timedScheduleBody'];

        // Create and assing timed schedule having hour set to 5
        $response = $this->createAndAssignSchedule($input);

        $data = ['amount' => 100];

        $payment = $this->fixtures->create('payment:captured', $data);

        $txn = $this->getLastTransaction(true);

        $time = Carbon::createFromTimestamp($txn['settled_at'], 'Asia/Kolkata');

        // Check if time is midnight
        $this->assertEquals(0, $time->hour);
    }

    public function testGetSchedule()
    {
        $schedule = $this->createSchedule();

        $response = $this->fetchSchedule($schedule['id']);

        $this->assertArraySelectiveEquals($this->testScheduleBody, $response);
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

    private function createAndAssignSchedule($input = null)
    {
        $schedule = $this->createSchedule($input);

        $request = $this->testData['testAssignScheduleById'];

        $request['content']['schedule_id'] = $schedule['id'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($schedule['id'], $response['schedule_id']);

        return $schedule;
    }

    private function deleteSchedule($id)
    {
        $request = $this->testData[__FUNCTION__];

        $request['url'] = $request['url'] . $id;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($this->testScheduleBody, $response);

        $schedules = $this->getEntities('schedule', ['deleted' => '1'], true);

        foreach ($schedules['items'] as $schedule)
        {
            if ($schedule['id'] === $id)
            {
                $this->assertNotNull($schedule['deleted_at']);
            }
        }
    }

    private function createSchedule($input = null)
    {
        if ($input === null)
        {
           $input = $this->getDefaultScheduleArray();
        }

        $request = $this->testData['createSchedule'];

        $request['content'] = $input;

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchSchedule($id)
    {
        $request = $this->testData[__FUNCTION__];

        $request['url'] = $request['url'] . $id;

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultScheduleArray()
    {
        return $this->testData['testScheduleBody'];
    }
}
