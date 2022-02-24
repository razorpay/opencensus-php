<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Mpan\Entity as MpanEntity;

class BharatQrController extends Controller
{
    public function processBharatQrPayment(string $gateway)
    {
        $inputTrace = Request::getContent();

        if (is_array($inputTrace) === true)
        {
            if (isset($inputTrace['mpan']) === true)
            {
                // logs only first 6 and last 4, mask remaining
                $inputTrace['mpan'] =  (new MpanEntity)->getMaskedMpan($inputTrace['mpan']);
            }

            unset($inputTrace['customer_name'], $inputTrace['MERCHANT_PAN']);
        }

        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $inputTrace,
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

            case 'upi_hulk':
                $input['headers']   = Request::header();
                $input['raw']       = Request::getContent();
                $input['content']   = Request::all();

                break;

            case 'hitachi' :
                $input['content'] = Request::all();
                $input['raw']     = Request::getContent();

                break;

            case 'upi_hdfc' :
                // currently we have only hdfc that is using mindgate as the acquirer.
                // later on if some other acquirer also uses mindgate, but needs some pre processing
                // then we can do that and later use mindgate code.
                $gateway = 'upi_mindgate';

            default:
                $input = Request::all();
        }

        $response = $this->service()->processPayment($input, $gateway);

        return ApiResponse::json($response);
    }

    public function processBharatQrPaymentInternal(string $gateway)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST_INTERNAL,
            [
                'input'   => Request::all(),
                'gateway' => $gateway,
            ]);

        $response = $this->service()->processPaymentInternal(Request::all(), $gateway);

        return ApiResponse::json($response);
    }

    public function processBharatQrTestPayment()
    {
        $gateway = Payment\Gateway::SHARP;

        return $this->processBharatQrPayment($gateway);
    }

    public function processBharatQrValidatePayment()
    {
        $input = Request::all();

        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_VALIDATE_REQUEST,
            [
                'input' => $input
            ]);

        $response = ['success' => true];

        return ApiResponse::json($response);
    }
}
