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

    public function fetchBalanceByIdAndParams($id)
    {
        $input = Request::all();

        $data = $this->service()->fetchBalanceByIdAndParams($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchBalanceById($id)
    {
        $data = $this->service()->fetchBalanceById($id);

        return ApiResponse::json($data);
    }

    public function fetchBalanceMultiple()
    {
        $input = Request::all();

        $response = $this->service()->fetchBalanceMultiple($input);

        return ApiResponse::json($response);
    }

    public function fetchBalancesForBalanceIds()
    {
        $input = Request::all();

        $response = $this->service()->fetchBalancesForBalanceIds($input);

        return ApiResponse::json($response);
    }

    public function fetchBalancesFoMerchantIds()
    {
        $input = Request::all();

        $response = $this->service()->fetchBalancesForMerchantIds($input);

        return ApiResponse::json($response);
    }
}
