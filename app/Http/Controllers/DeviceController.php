<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

use RZP\Models\Device;

class DeviceController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Device\Service;
    }

    public function createDevice()
    {
        $input = Request::all();

        $device = $this->service->create($input);

        return ApiResponse::json($device);
    }

    public function verifyDevice()
    {
        $input = Request::all();

        $device = $this->service->verifyAndGetToken($input);
    }
}
