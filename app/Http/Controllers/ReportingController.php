<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services;

class ReportingController extends Controller
{
    /**
     * @var Services\Reporting
     */
    protected $reportingService;

    public function __construct()
    {
        parent::__construct();

        $this->reportingService = $this->app['reporting'];
    }

    public function getConfig(string $id)
    {
        $data = $this->reportingService->fetchConfigById($id);

        return ApiResponse::json($data);
    }

    public function listConfig()
    {
        $input = Request::all();

        $data = $this->reportingService->fetchConfigMultiple($input);

        return ApiResponse::json($data);
    }

    public function createConfig()
    {
        $input = Request::all();

        $data = $this->reportingService->createConfig($input);

        return ApiResponse::json($data);
    }

    public function updateConfig($id)
    {
        $input = Request::all();

        $data = $this->reportingService->editConfig($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteConfig(string $id)
    {
        $data = $this->reportingService->deleteConfig($id);

        return ApiResponse::json($data);
    }

    public function generateReport(string $configId)
    {
        $input = Request::all();

        $data = $this->reportingService->generateReport($configId, $input);

        return ApiResponse::json($data);
    }

    public function getLog(string $id)
    {
        $data = $this->reportingService->fetchLogById($id);

        return ApiResponse::json($data);
    }

    public function listLog()
    {
        $input = Request::all();

        $data = $this->reportingService->fetchLogMultiple($input);

        return ApiResponse::json($data);
    }
}
