<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;

class UpiTransferController extends Controller
{
    public function processUpiTransferPayment($acquirer, $gateway)
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'input' => Request::getContent(),
            ]);

        // For UPI ICICI, bank send only the encrypted message,
        // whereas for UPI Mindgate, bank send a string which contains
        // the encrypted message and pgMerchantId which is used to decrypted the message.

        switch ($gateway)
        {
            case 'upi_icici' :
                $input = Request::getContent();

                break;

            default:
                $input = Request::all();
        }

        $response = $this->service()->processUpiTransferPayment($input, $gateway);

        return ApiResponse::json($response);
    }

    public function fetchForPayment(string $paymentId)
    {
        $response = $this->service()->fetchForPayment($paymentId);

        return ApiResponse::json($response);
    }
}
