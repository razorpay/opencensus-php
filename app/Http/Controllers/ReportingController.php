<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

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

    public function get(string $id)
    {
        $input = Request::all();

        $data = $this->reportingService->fetchConfigById($id, $input);

        return ApiResponse::json($data);
    }

    public function list()
    {
        $input = Request::all();

        $data = $this->reportingService->fetchConfigMultiple($input);

        return ApiResponse::json($data);
    }

    public function create()
    {
        $input = Request::all();

        $data = $this->reportingService->createConfig($input);

        return ApiResponse::json($data);
    }

    public function update($id)
    {
        $input = Request::all();

        $data = $this->reportingService->editConfig($id, $input);

        return ApiResponse::json($data);
    }

    public function delete(string $id)
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
}
