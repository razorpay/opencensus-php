<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BalanceController extends Controller
{
    public function postUpdateFreePayout($id)
    {
        $input = Request::all();

        $response = $this->service()->updateFreePayout($id, $input);

        return ApiResponse::json($response);
    }
}
