<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use ApiResponse;
use RZP\Models\QrPayment\Service;

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

        $entities = (new Service())->fetchPaymentsForQrCode($input, $id);

        return ApiResponse::json($entities);
    }

    public function fetchCapturedPaymentByQrCodeId($qrCodeId)
    {
        $response = (new Service())->fetchCapturedPaymentByQrCodeId($qrCodeId);

        return ApiResponse::json($response);
    }

}
