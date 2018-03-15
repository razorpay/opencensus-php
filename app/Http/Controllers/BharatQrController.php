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
            //
            // in case of upi icici
            // input is in form of text
            //
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
