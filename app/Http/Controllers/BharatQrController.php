<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BharatQrController extends Controller
{
    public function processBharatQrPayment(string $gateway)
    {
        switch ($gateway)
        {
            case 'icici' :
                $input = Request::getContent();

                break;

            default:
                $input = Request::all();
        }

        $response = $this->service()->processPayment($input, $gateway);

        return ApiResponse::json($response);
    }
}
