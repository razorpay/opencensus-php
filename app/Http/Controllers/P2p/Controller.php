<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Http\Controllers;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;

class Controller extends Controllers\Controller
{
    protected function request(): HttpRequest
    {
        return Request::getFacadeRoot();
    }

    protected function response(array $response)
    {
        return response($response, 200, [
            'Content-Type'          => 'application/json',
            'X-Razorpay-Request-Id' => str_random(40),
        ]);
    }
}
