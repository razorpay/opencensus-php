<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class RizeIncorporationPaymentController extends Controller
{
    public function rizePaymentLinkCallback()
    {
        $input = Request::getContent();

        $response = $this->service(E::MERCHANT)->rizePaymentsCallback($input, TraceCode::MERCHANT_RIZE_PAYMENTLINK_CALLBACK, 'merchant_rize_paymentlink_callback');

        return ApiResponse::json($response);
    }

    public function rizePaymentCallback()
    {
        $input = Request::getContent();

        $response = $this->service(E::MERCHANT)->rizePaymentsCallback($input, TraceCode::MERCHANT_RIZE_PAYMENT_CALLBACK, 'merchant_rize_payment_callback');

        return ApiResponse::json($response);
    }

}
