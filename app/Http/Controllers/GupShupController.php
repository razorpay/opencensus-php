<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class GupShupController extends Controller
{
    public function handleIncomingMessagesCallback()
    {
        $input = Request::all();

        $response = $this->service()->handleIncomingMessagesCallback($input);

        return ApiResponse::json($response);
    }
}
