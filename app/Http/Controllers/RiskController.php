<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RiskController extends Controller
{
    public function postRiskEntity(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service('risk')->create($paymentId, $input);

        return ApiResponse::json($data);
    }

    public function putRiskEntity(string $id)
    {
        $input = Request::all();

        $data = $this->service('risk')->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getRiskForAllPayments()
    {
        $data = $this->service('risk')->getRiskForAllPayments();

        return ApiResponse::json($data);
    }

    public function getRiskPaymentsForMerchant(string $merchantId)
    {
        $data = $this->service('risk')->getRiskPaymentsForMerchant($merchantId);

        return ApiResponse::json($data);
    }

    public function getRiskForPayment(string $paymentId)
    {
        $data = $this->service('risk')->getRiskForPayment($paymentId);

        return ApiResponse::json($data);
    }
}
