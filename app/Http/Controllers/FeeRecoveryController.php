<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class FeeRecoveryController extends Controller
{
    public function createRecoveryPayout()
    {
        $input = Request::all();

        $response = $this->service()->createRecoveryPayout($input);

        return ApiResponse::json($response);
    }
}
