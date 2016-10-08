<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Order;
use Request;

class OrderController extends Controller
{
    protected $order;

    public function __construct()
    {
        $this->order = new Order\Service;
    }

    public function createOrder()
    {
        $input = Request::all();

        $data = $this->order->create($input);

        return ApiResponse::json($data);
    }

    public function getOrders()
    {
        $input = Request::all();

        $data = $this->order->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $data = $this->order->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $payments = $this->order->fetchPaymentsFor($id);

        return ApiResponse::json($payments);
    }
}

