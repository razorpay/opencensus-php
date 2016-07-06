<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Order;

class OrderController extends BaseController
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

