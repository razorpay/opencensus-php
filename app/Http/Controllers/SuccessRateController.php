<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Services\Doppler;

class SuccessRateController extends Controller
{
    public function proxy()
    {
        $method = Request::method();

        $path = Request::path() . '?' . Request::getQueryString();

        $content = Request::getContent();

        $response = $this->app['doppler']->sendRequest($method, $path, $content);

        return ApiResponse::json($response);
    }
}
