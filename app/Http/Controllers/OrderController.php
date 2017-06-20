<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Order;

class OrderController extends Controller
{
    public function createOrder()
    {
        $input = Request::all();

        $data = $this->service('order')->create($input);

        return ApiResponse::json($data);
    }

    public function getOrders()
    {
        $input = Request::all();

        $data = $this->service('order')->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $data = $this->service('order')->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $payments = $this->service('order')->fetchPaymentsFor($id);

        return ApiResponse::json($payments);
    }
}

