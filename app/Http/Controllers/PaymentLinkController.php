<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Http\Controllers\Traits\HasCrudMethods;

class PaymentLinkController extends Controller
{
    use HasCrudMethods;

    public function fetchPayments(string $id)
    {
        $input = Request::all();

        $payments = $this->service()->fetchPayments($id, $input);

        return ApiResponse::json($payments);
    }
}
