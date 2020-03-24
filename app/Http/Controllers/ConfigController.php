<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ConfigController extends Controller
{
    public function createPaymentConfig()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function fetchPaymentConfig(string $type)
    {
        $configs = $this->service()->fetch($type);

        return ApiResponse::json($configs);
    }

    public function updatePaymentConfig()
    {
        $input = Request::all();

        $data = $this->service()->update($input);

        return ApiResponse::json($data);
    }

}
