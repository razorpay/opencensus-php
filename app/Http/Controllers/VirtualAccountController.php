<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

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
