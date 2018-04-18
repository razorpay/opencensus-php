<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class RazorxController extends Controller
{
    public function sendRequest($path = '')
    {
        $response['message'] = "will send a request to razorx";

        return ApiResponse::json($response);
    }
}
