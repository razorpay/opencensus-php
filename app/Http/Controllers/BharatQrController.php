<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class BharatQrController extends Controller
{
    public function processBharatQrPayment(string $gateway)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => Request::getContent(),
                'gateway' => $gateway,
            ]);

        switch ($gateway)
        {
            //
            // in case of upi icici
            // input is in form of text
            //
            case 'upi_icici' :
                $input = Request::getContent();

                break;

            default:
                $input = Request::all();
        }

        $response = $this->service()->processPayment($input, $gateway);

        return ApiResponse::json($response);
    }

    public function processBharatQrTestPayment()
    {
        $gateway = Payment\Gateway::SHARP;

        return $this->processBharatQrPayment($gateway);
    }
}
