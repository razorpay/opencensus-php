<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class ReportingController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->reportingService = $this->app['reporting'];
    }

    public function get($id)
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

    public function delete($id)
    {
        $data = $this->reportingService->deleteConfig($id);

        return ApiResponse::json($data);
    }

    public function generateReport($configId)
    {
        $input = Request::all();

        $data = $this->reportingService->generateReport($configId, $input);

        return ApiResponse::json($data);
    }
}
