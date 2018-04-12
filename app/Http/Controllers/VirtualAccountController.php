<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function createForOrder(string $id)
    {
        $input = Request::all();

        $response = $this->service()->createForOrder($id, $input);

        return ApiResponse::json($response);
    }

    public function getPayments(string $id)
    {
        $response = $this->service()->fetchPayments($id);

        return ApiResponse::json($response);
    }

    public function refundExcessPayments()
    {
        $data = $this->service()->refundExcessPayments();

        return ApiResponse::json($data);
    }
}
