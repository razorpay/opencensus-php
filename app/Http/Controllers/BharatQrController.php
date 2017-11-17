<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BharatQrController extends Controller
{
    public function processBharatQrPayment()
    {
        $input = Request::all();

        $response = $this->service()->processPayment($input);

        return ApiResponse::json($response);
    }
}
