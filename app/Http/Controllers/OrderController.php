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
        $input = Request::all();

        $payments = $this->service()->fetchPaymentsFor($id, $input);

        return ApiResponse::json($payments);
    }

    public function editNotes($id)
    {
        $input = Request::all();

        $data = $this->service()->editNotes($id, $input);

        return ApiResponse::json($data);
    }
}
