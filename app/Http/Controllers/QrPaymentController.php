<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use ApiResponse;
use RZP\Constants\HyperTrace;
use RZP\Models\QrPayment\Service;
use RZP\Trace\Tracer;

class QrPaymentController extends Controller
{
    public function fetchMultiplePayments()
    {
        $input = Request::all();

        $entities = (new Service())->fetchMultiplePayments($input);

        return ApiResponse::json($entities);
    }

    public function fetchQrCodePayments($id)
    {
        $input = Request::all();

        $entities = Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_PAYMENT_BY_QR_CODE_ID], function () use ($input, $id) {
            return (new Service())->fetchPaymentsForQrCode($input, $id);
        });

        return ApiResponse::json($entities);
    }

    public function fetchCapturedPaymentByQrCodeId($qrCodeId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_PAYMENT_BY_QR_CODE_ID], function () use ($qrCodeId) {
            return (new Service())->fetchCapturedPaymentByQrCodeId($qrCodeId);
        });

        return ApiResponse::json($response);
    }

}
