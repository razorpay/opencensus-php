<?php

namespace RZP\Http\Controllers;
use Request;
use ApiResponse;
use RZP\Trace\TraceCode;

class SmartRoutingController extends Controller
{
    public function proxy()
    {
        $input = Request::all();

        $method = Request::method();

        $path = str_replace("v1","",Request::path());

        $action =[
            'url'       => $path,
            'method'    => $method
        ];
        $this->app['trace']->info(TraceCode::SMART_ROUTING_REQUEST_PROXY,$input);

        $response = $this->app['smartRouting']->sendRequest($action,$input,null,null,0.5);

        return ApiResponse::json($response);
    }

    public function refreshCron()
    {
        $method = Request::method();

        $path = str_replace("v1","",Request::path());

        $action =[
            'url'       => $path,
            'method'    => $method
        ];
        $this->app['trace']->info(TraceCode::SMART_ROUTING_REQUEST_PROXY, $action);

        $response = $this->app['smartRouting']-> refreshSmartRoutingCache();

        return ApiResponse::json($response);

    }
}
