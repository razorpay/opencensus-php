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

        $this->testScheduleBody = $request = $this->testData['testScheduleBody'];
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
        $request = $this->getValidScheduleBody();

        unset($request['content']['anchor']);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Carbon::MONDAY, $response['anchor']);
    }

    public function testScheduleInvalidPeriod()
    {
        $request = $this->getValidScheduleBody();

        $request['content']['period'] = 'invalidPeriod';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request) {
            $this->makeRequestAndGetContent($request);
        });
    }

    public function testScheduleInvalidWeeklyAnchor()
    {
        $request = $this->getValidScheduleBody();

        $request['content']['anchor'] = Carbon::SATURDAY;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request) {
            $this->makeRequestAndGetContent($request);
        });
    }

    public function testScheduleInvalidType()
    {
        $request = $this->getValidScheduleBody();

        $request['content']['type'] = 'not_settlement';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request) {
            $this->makeRequestAndGetContent($request);
        });
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

        $this->runRequestResponseFlow($data, function() use ($schedule) {
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

    public function testAssignSchedule()
    {
        $request = $this->testData[__FUNCTION__];

        $request['content'] = $this->testScheduleBody;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['settlement_schedule_id']);

        $response = $this->fetchSchedule($response['settlement_schedule_id']);

        $this->assertArraySelectiveEquals($this->testScheduleBody, $response);
    }

    private function createAndAssignSchedule()
    {
        $schedule = $this->createSchedule();

        $request = $this->testData['testAssignScheduleById'];

        $request['content']['settlement_schedule_id'] = $schedule['id'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($schedule['id'], $response['settlement_schedule_id']);

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

    private function createSchedule()
    {
        $request = $this->getValidScheduleBody();

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

    private function getValidScheduleBody()
    {
        $request = $this->testData['createSchedule'];

        $request['content'] = $this->testScheduleBody;

        return $request;
    }
}
