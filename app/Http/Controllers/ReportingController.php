<?php

namespace RZP\Http\Controllers;

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

    public function updateConfig($id)
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

    protected function reportingService(): Reporting
    {
        return new Reporting();
    }
}
