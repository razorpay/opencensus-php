<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BharatQrController extends Controller
{
    public function processBharatQrPayment(string $gateway)
    {
        $input = Request::all();

        $response = $this->service()->processPayment($input, $gateway);

        return ApiResponse::json($response);
    }
}
