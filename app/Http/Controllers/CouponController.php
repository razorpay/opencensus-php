<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Coupon;

class CouponController extends Controller
{
    public function createCoupon()
    {
        $input = Request::all();

        $data = (new Coupon\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function fetchCoupons()
    {
        $input = Request::all();

        $data = (new Coupon\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}
