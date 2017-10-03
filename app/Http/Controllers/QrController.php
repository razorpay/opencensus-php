<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class QrController extends Controller
{
    public function processQrPayment()
    {
        $input = Request::all();

        $response = $this->service()->processPayment($input);

        return ApiResponse::json($response);
    }
}
