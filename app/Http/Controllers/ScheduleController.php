<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class ScheduleController extends Controller
{
    public function postSchedule()
    {
        $input = Request::all();

        $data = $this->service()->createSchedule($input);

        return ApiResponse::json($data);
    }

    public function getSchedule($id)
    {
        $data = $this->service()->getScheduleById($id);

        return ApiResponse::json($data);
    }

    public function deleteSchedule($id)
    {
        $data = $this->service()->deleteSchedule($id);

        return ApiResponse::json($data);
    }

    public function getSchedules()
    {
        $input = Request::all();

        $data = $this->service()->getAllSchedules($input);

        return ApiResponse::json($data);
    }

    public function getSettlementSchedules()
    {
        $data = $this->service()->getSettlementSchedules();

        return ApiResponse::json($data);
    }

    public function getScheduleTasks($type)
    {
        $data = $this->service()->getScheduleTasks($type);

        return ApiResponse::json($data);
    }

    public function putSchedule($id)
    {
        $input = Request::all();

        $data = $this->service()->editSchedule($id, $input);

        return ApiResponse::json($data);
    }

    public function updateNextRun()
    {
        $input = Request::all();

        $data = $this->service()->updateNextRun($input);

        return ApiResponse::json($data);
    }

    public function processTasks()
    {
        $input = Request::all();

        $data = $this->service()->processTasks($input);

        return ApiResponse::json($data);
    }

    public function createFeeRecoveryScheduleTask()
    {
        $input = Request::all();

        $data = $this->service()->createFeeRecoveryScheduleTask($input);

        return ApiResponse::json($data);
    }
}
