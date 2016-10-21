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

        $data = (new Schedule\Service)->createSchedule($input);

        return ApiResponse::json($data);
    }

    public function getSchedule($id)
    {
        $data = (new Schedule\Service)->getScheduleById($id);

        return ApiResponse::json($data);
    }

    public function putSchedule($id)
    {
        $input = Request::all();

        $data = (new Schedule\Service)->editSchedule($id, $input);

        return ApiResponse::json($data);
    }

    public function assignSchedule()
    {
        $input = Request::all();

        $data = (new Schedule\Service)->assignSchedule($input);

        return ApiResponse::json($data);
    }
}
