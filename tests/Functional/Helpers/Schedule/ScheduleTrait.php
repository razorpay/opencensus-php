<?php

namespace RZP\Tests\Functional\Helpers\Schedule;

trait ScheduleTrait
{
    private function createSchedule($input = null)
    {
        if ($input === null)
        {
           $input = $this->getDefaultScheduleArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchSchedule($id)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/schedules/' . $id,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function deleteSchedule($id)
    {
        $request = [
            'method'  => 'DELETE',
            'url'     => '/schedules/' . $id,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function createAndAssignSchedule($input = null)
    {
        $schedule = $this->createSchedule($input);

        $request = $this->testData['testAssignScheduleById'];

        $request['content']['schedule_id'] = $schedule['id'];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($schedule['id'], $response['schedule_id']);

        return $schedule;
    }

    private function getDefaultScheduleArray()
    {
        return [
            'name'       => 'Every Wednesday',
            'type'       => 'settlement',
            'period'     => 'weekly',
            'interval'   => 1,
            'anchor'     => 3,
            'delay'      => 1,
            'next_run'   => 1452105000,
        ];
    }
}