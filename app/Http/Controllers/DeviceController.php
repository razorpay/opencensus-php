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

    public function createDevice()
    {
        $input = Request::all();

        $device = $this->service('device')->create($input);

        return ApiResponse::json($device);
    }

    public function getDevice($deviceId)
    {
        $invoice = $this->service('device')->fetch($deviceId);

        return ApiResponse::json($invoice);
    }

    public function verifyDevice()
    {
        $input = Request::all();

        $this->service('device')->verify($input);

        return ApiResponse::json([], 200);
    }

    public function refreshUpiToken()
    {
        $input = Request::all();

        $this->service('device')->refreshToken($input);

        return ApiResponse::json([], 204);
    }
}
