<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class RtoDashboardController extends Controller
{

    protected function list()
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $response = $this->app['rto_dashboard_service']->list($input, $merchantId);

        $precision = ini_get('serialize_precision');

        ini_set('serialize_precision', -1);

        $apiResponse = ApiResponse::json($response);

        ini_set('serialize_precision', $precision);

        return $apiResponse;
    }
}
