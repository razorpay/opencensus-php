<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

class DeviceController extends Controller
{
    protected $service;

    public function createDevice()
    {
        $input = Request::all();

        $device = $this->service()->create($input);

        return ApiResponse::json($device);
    }

    public function getDevice($deviceId)
    {
        $invoice = $this->service()->fetch($deviceId);

        return ApiResponse::json($invoice);
    }

    public function verifyDevice()
    {
        $input = Request::all();

        $this->service()->verify($input);

        return ApiResponse::json([], 200);
    }

    public function refreshUpiToken()
    {
        $input = Request::all();

        $this->service()->refreshToken($input);

        return ApiResponse::json([], 204);
    }
}
