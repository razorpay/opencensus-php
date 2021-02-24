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

    private function editSchedule($id, $input)
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/schedules/'.$id,
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

    private function createAndAssignScheduleAndAssertId($input = null)
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
            'period'     => 'weekly',
            'interval'   => 1,
            'anchor'     => 3,
            'delay'      => 1,
            'type'       => 'settlement',
        ];
    }

    protected function assignSchedule($scheduleId, $request)
    {

        $request['content']['schedule_id'] = $scheduleId;

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
