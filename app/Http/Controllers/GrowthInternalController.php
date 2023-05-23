<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Growth;

class GrowthInternalController extends Controller
{
    protected $service = Growth\Service::class;

    public function sendPricingBundleEmail()
    {
        $input = Request::all();

        $data = $this->service()->sendPricingBundleEmail($input);

        return ApiResponse::json($data);
    }

    public function addAmountCredits()
    {
        $input = Request::all();

        $data = $this->service()->addAmountCredits($input);

        return ApiResponse::json($data);
    }
}
