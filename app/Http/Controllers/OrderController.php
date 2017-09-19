<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class OrderController extends Controller
{
    public function createOrder()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function getOrders()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $payments = $this->service()->fetchPaymentsFor($id);

        return ApiResponse::json($payments);
    }
}

