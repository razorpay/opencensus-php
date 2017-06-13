<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Coupon;

class CouponController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = \RZP\Models\Coupon\Service::class;

    public function apply()
    {
        $input = Request::all();

        $data = $this->service()->apply($input);

        return ApiResponse::json($data);
    }
}
