<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\Ecollect;
use ApiResponse;
use Request;

class EcollectController extends Controller
{
    public function validateEcollect()
    {
        $input = Request::all();

        $response = (new Ecollect\Service)->validate($input);

        return ApiResponse::json($response);
    }

    public function payEcollect()
    {
        $input = Request::all();

        $response = (new Ecollect\Service)->pay($input);

        return ApiResponse::json($response);
    }
}
