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

    public function getRiskEntityMultiple()
    {
        $input = Request::all();

        $data = $this->service('risk')->getRiskForAllPayments($input);

        return ApiResponse::json($data);
    }
}
