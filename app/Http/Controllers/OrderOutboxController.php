<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class OrderOutboxController extends Controller
{
    public function retryOrderUpdate()
    {
        $input = Request::all();

        $data = $this->service()->retryOrderUpdate($input);

        return ApiResponse::json($data);
    }

    public function createOrderOutboxPartition()
    {
        $response = $this->service()->createOrderOutboxPartition();

        return ApiResponse::json($response);
    }
}
