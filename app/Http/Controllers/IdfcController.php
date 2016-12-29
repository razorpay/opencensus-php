<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

class IdfcController extends Controller
{
    public function registerDevice()
    {
        $input = Request::all();

        $device = $this->generateFakeDevice($input);

        $id = $device['id'];
        $verification_id = $device['id'];

        Cache::forever("devices.$id", $device);
        Cache::forever("devices.verification.$verification_id", $id);

        return ApiResponse::json($device);
    }

    protected function makeRequest($api)
    {
        $gw = new \RZP\Gateway\Upi\Idfc;

        $gw->makeRequest($api, $params)
    }
}
