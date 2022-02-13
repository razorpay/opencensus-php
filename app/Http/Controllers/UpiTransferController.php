<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Base\ConnectionType;
use RZP\Constants\HyperTrace;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Trace\Tracer;

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
        $response = Tracer::inSpan(['name' => HyperTrace::UPI_FETCH_FOR_PAYMENT], function() use($paymentId)
        {
            return $this->service()->fetchForPayment($paymentId);
        });

        return ApiResponse::json($response);
    }

    public function processUpiTransferPaymentInternal($gateway)
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST_INTERNAL,
            [
                'input' => Request::getContent(),
                'gateway' => $gateway,
            ]);

        switch ($gateway)
        {
            case Gateway::UPI_ICICI :
                $input = Request::getContent();

                break;

            default:
                $input = Request::all();
        }

        $response = $this->service()->processUpiTransferPaymentInternal($input, $gateway);

        return ApiResponse::json($response);
    }
}
