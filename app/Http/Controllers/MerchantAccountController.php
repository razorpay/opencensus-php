<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class MerchantAccountController extends Controller
{
    public function createMerchantAccount()
    {
        $input = Request::all();

        $data = $this->service()->createMerchantAccount($input);

        $response = [
            'data' => $data,
        ];

        return ApiResponse::json($response);
    }
}
