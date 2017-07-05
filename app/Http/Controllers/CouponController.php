<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use \RZP\Models\Coupon\Service;

class CouponController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Service::class;

    public function apply()
    {
        $input = Request::all();

        $data = $this->service()->apply($input);

        return ApiResponse::json($data);
    }
}
