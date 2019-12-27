<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Services\Doppler;

class SuccessRateController extends Controller
{
    /**
     * Here in this function we are passing the
     * request coming from dashboard to doppler
     */

    public function proxy()
    {
        $method = Request::method();

        $path = Request::path() . '?' . Request::getQueryString();

        $content = Request::getContent();

        $this->trace->info(TraceCode::DOPPLER_IN_CONTROLLER, [
            'method' => $method,
            'path' => $path,
            'content' => $content,
        ]);

        $response = $this->app['doppler']->sendRequest($method, $path, $content);

        $this->trace->info(TraceCode::DOPPLER_IN_CONTROLLER_RESPONSE, $response);

        return ApiResponse::json($response);
    }
}
