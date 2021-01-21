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

    public function createCapitalBalance()
    {
        $input = Request::all();

        $response = $this->service()->createCapitalBalance($input);

        return ApiResponse::json($response);
    }

    public function fetchBalanceById($id)
    {
        $input = Request::all();

        $data = $this->service()->fetchBalanceById($id, $input);

        return ApiResponse::json($data);
    }
}
