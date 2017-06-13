<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\Ecollect;
use ApiResponse;
use Request;

class EcollectController extends Controller
{
    public function validateEcollect(Ecollect\Service $service)
    {
        $input = Request::all();

        $response = $service->validate($input);

        return ApiResponse::json($response);
    }

    public function payEcollect(Ecollect\Service $service)
    {
        $input = Request::all();

        $response = $service->pay($input);

        return ApiResponse::json($response);
    }
}
