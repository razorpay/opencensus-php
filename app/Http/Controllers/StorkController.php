<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;

use RZP\Services\Stork;

class StorkController extends Controller
{
    public function setMerchantTemplateRateLimitThreshold()
    {
        $input = Request::all();

        $data = (new Stork)->setMerchantTemplateRateLimitThreshold($input);

        return ApiResponse::json($data);
    }

    public function deleteMerchantTemplateRateLimitThreshold()
    {
        $input = Request::all();

        $data = (new Stork)->deleteMerchantTemplateRateLimitThreshold($input);

        return ApiResponse::json($data);
    }

    public function removeSuppressionListEntry()
    {
        $input = Request::all();

        $data = (new Stork)->removeSuppressionListEntry($input);

        return ApiResponse::json($data);
    }
}
