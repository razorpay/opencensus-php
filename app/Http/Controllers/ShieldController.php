<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ShieldController extends Controller
{
    public function proxyRequest()
    {
        // v1/shield -> 9 chars
        $requestUri = substr(Request::path(), 9);

        $queryString = Request::getQueryString();

        if (is_null($queryString) === false)
        {
            $requestUri = $requestUri . '?' . $queryString;
        }

        $payload = Request::json()->all();

        $response = $this->app['shield']->sendRequestV2($requestUri, Request::method(), $payload);

        return ApiResponse::json($response);
    }
}
