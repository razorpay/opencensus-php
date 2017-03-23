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

        $response = $this->service('ecollect')->validate($input);

        return ApiResponse::json($response);
    }

    public function payEcollect()
    {
        $input = Request::all();

        $response = $this->service('ecollect')->pay($input);

        return ApiResponse::json($response);
    }
}
