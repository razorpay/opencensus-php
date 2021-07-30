<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CouponController extends Controller
{
    use Traits\HasCrudMethods;

    public function apply()
    {
        $input = Request::all();

        $data = $this->service()->apply($input);

        return ApiResponse::json($data);
    }

    public function validateCoupon()
    {
        $input = Request::all();

        $data  = $this->service()->validateCouponAndGetDetails($input);

        return ApiResponse::json($data);
    }

    public function applyMtuCoupon()
    {
        $input = Request::all();

        $response = $this->service()->applyMtuCoupon($input);

        return ApiResponse::json($response);
    }
}
