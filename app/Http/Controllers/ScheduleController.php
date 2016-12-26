<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Schedule;

class ScheduleController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new Schedule\Service;
    }

    public function postSchedule()
    {
        $input = Request::all();

        $data = $this->service->createSchedule($input);

        return ApiResponse::json($data);
    }

    public function getSchedule($id)
    {
        $data = $this->service->getScheduleById($id);

        return ApiResponse::json($data);
    }

    public function deleteSchedule($id)
    {
        $data = $this->service->deleteSchedule($id);

        return ApiResponse::json($data);
    }

    public function getSchedules()
    {
        $input = Request::all();

        $data = $this->service->getAllSchedules($input);

        return ApiResponse::json($data);
    }

    public function putSchedule($id)
    {
        $input = Request::all();

        $data = $this->service->editSchedule($id, $input);

        return ApiResponse::json($data);
    }
}
