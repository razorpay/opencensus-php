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

    public function getOrder()
    {
        $input = Input::all();

        $data = $this->order->fetch($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $input = Input::all();

        $data = $this->order->fetchById($id);

        return ApiResponse::json($data);
    }

    public function updateOrder()
    {
        $input = Input::all();

        $data = $this->order->update($input);

        return ApiResponse::json($data);
    }
}

