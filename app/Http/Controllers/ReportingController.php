<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services\Reporting;

class ReportingController extends Controller
{
    public function getConfig(string $id)
    {
        $data = $this->reportingService()->fetchConfigById($id);

        return ApiResponse::json($data);
    }

    public function listConfig()
    {
        $data = $this->reportingService()->fetchConfigMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function createConfig()
    {
        $data = $this->reportingService()->createConfig($this->input);

        return ApiResponse::json($data);
    }

    public function updateConfig(string $id)
    {
        $data = $this->reportingService()->editConfig($id, $this->input);

        return ApiResponse::json($data);
    }

    public function deleteConfig(string $id)
    {
        $data = $this->reportingService()->deleteConfig($id);

        return ApiResponse::json($data);
    }

    public function createLog()
    {
        $data = $this->reportingService()->createLog($this->input);

        return ApiResponse::json($data);
    }

    public function getLog(string $id)
    {
        $data = $this->reportingService()->fetchLogById($id);

        return ApiResponse::json($data);
    }

    public function listLog()
    {
        $data = $this->reportingService()->fetchLogMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function getSchedule(string $id)
    {
        $data = $this->reportingService()->fetchScheduleById($id);

        return ApiResponse::json($data);
    }

    public function listSchedule()
    {
        $data = $this->reportingService()->fetchScheduleMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function createSchedule()
    {
        $data = $this->reportingService()->createSchedule($this->input);

        return ApiResponse::json($data);
    }

    public function updateSchedule(string $id)
    {
        $data = $this->reportingService()->editSchedule($id, $this->input);

        return ApiResponse::json($data);
    }

    public function deleteSchedule(string $id)
    {
        $data = $this->reportingService()->deleteSchedule($id);

        return ApiResponse::json($data);
    }

    public function triggerSchedule(string $id)
    {
        $input = Request::all();

        $data = $this->reportingService()->triggerSchedule($id, $input['merchant_id']);

        return ApiResponse::json($data);
    }

    /**
     * Returns reporting service instance. It's not in constructor as it
     * depends on ba's vars which get set in middleware.
     *
     * @return Reporting
     */
    protected function reportingService(): Reporting
    {
        return new Reporting();
    }
}
