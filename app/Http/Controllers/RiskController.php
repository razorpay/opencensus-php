<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RiskController extends Controller
{
    public function postRiskEntity(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service('risk')->createRiskEntry($paymentId, $input);

        return ApiResponse::json($data);
    }

    public function putRiskEntity(string $id)
    {
        $input = Request::all();

        $data = $this->service('risk')->editRiskEntry($id, $input);

        return ApiResponse::json($data);
    }

    public function getRiskForAllPayments()
    {
        $data = $this->service('risk')->getRiskForAllPayments();

        return ApiResponse::json($data);
    }

    public function getRiskForPayment(string $paymentId)
    {
        $data = $this->service('risk')->getRiskForPayment($paymentId);

        return ApiResponse::json($data);
    }
}
