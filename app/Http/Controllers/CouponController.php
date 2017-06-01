<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Coupon;

class CouponController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = (new Coupon\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function fetchMultiple()
    {
        $input = Request::all();

        $data = (new Coupon\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function delete($id)
    {
        $data = (new Coupon\Service)->delete($id);

        return ApiResponse::json($data);
    }
}
