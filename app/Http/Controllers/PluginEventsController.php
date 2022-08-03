<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PluginEventsController extends Controller
{
    public function trackSegment()
    {
        $input = Request::all();

        $segmentClient = $this->app['plugins-segment'];

        $response = $segmentClient->sendEventToSegment($input);

        return ApiResponse::json($response);
    }
}
