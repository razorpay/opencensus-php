<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class CpsController extends Controller
{
    public function syncGatewayEntities()
    {
        $input = Request::all();

        $response = $this->app['cps']->syncCron($input);

        return response()->json($response);
    }
}
