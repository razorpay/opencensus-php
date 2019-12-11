<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;

class UpiTransferController extends Controller
{
    public function processUpiTransferPayment()
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'input' => Request::getContent(),
            ]);

        $input = Request::all();

        $response = $this->service()->processPaymentForUpiMindgate($input);

        return ApiResponse::json($response);
    }

    public function fetchForPayment(string $paymentId)
    {
        $response = $this->service()->fetchForPayment($paymentId);

        return ApiResponse::json($response);
    }
}
