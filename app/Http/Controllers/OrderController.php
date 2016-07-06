<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Order;

class OrderController extends Controller
{
    protected $order;

    public function __construct()
    {
        $this->order = new Order\Service();
    }

    public function createOrder()
    {
        $input = Input::all();

        $data = $this->order->create($input);

        return ApiResponse::json($data);
    }

    public function getOrders()
    {
        $input = Input::all();

        $data = $this->order->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $input = Input::all();

        $data = $this->order->fetch($id);

        return ApiResponse::json($data);
    }

    public function updateOrder()
    {
        $input = Input::all();

        $data = $this->order->update($input);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $input = Input::all();

        $payments = $this->order->fetchPaymentsFor($id);

        return ApiResponse::json($payments);
    }
}

