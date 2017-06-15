<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Schedule;

class ScheduleController extends Controller
{
    public function postSchedule()
    {
        $input = Request::all();

        $data = $this->service('schedule')->createSchedule($input);

        return ApiResponse::json($data);
    }

    public function getSchedule($id)
    {
        $data = $this->service('schedule')->getScheduleById($id);

        return ApiResponse::json($data);
    }

    public function deleteSchedule($id)
    {
        $data = $this->service('schedule')->deleteSchedule($id);

        return ApiResponse::json($data);
    }

    public function getSchedules()
    {
        $input = Request::all();

        $data = $this->service('schedule')->getAllSchedules($input);

        return ApiResponse::json($data);
    }

    public function putSchedule($id)
    {
        $input = Request::all();

        $data = $this->service('schedule')->editSchedule($id, $input);

        return ApiResponse::json($data);
    }
}
