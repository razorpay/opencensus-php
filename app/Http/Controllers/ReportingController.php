<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class ReportingController extends Controller
{
    /**
     * @var \RZP\Services\Reporting
     */
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['reporting'];
    }

    public function getConfig(string $id)
    {
        $data = $this->service->fetchConfigById($id);

        return ApiResponse::json($data);
    }

    public function listConfig()
    {
        $data = $this->service->fetchConfigMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function createConfig()
    {
        $data = $this->service->createConfig($this->input);

        return ApiResponse::json($data);
    }

    public function updateConfig($id)
    {
        $data = $this->service->editConfig($id, $this->input);

        return ApiResponse::json($data);
    }

    public function deleteConfig(string $id)
    {
        $data = $this->service->deleteConfig($id);

        return ApiResponse::json($data);
    }

    public function createLog()
    {
        $data = $this->service->createLog($this->input);

        return ApiResponse::json($data);
    }

    public function getLog(string $id)
    {
        $data = $this->service->fetchLogById($id);

        return ApiResponse::json($data);
    }

    public function listLog()
    {
        $data = $this->service->fetchLogMultiple($this->input);

        return ApiResponse::json($data);
    }
}
