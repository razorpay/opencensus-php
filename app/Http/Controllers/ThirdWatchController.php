<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services;

class ThirdWatchController
{
    public function checkAddressServiceability()
    {
        $input = Request::all();

        $response = (new Services\ThirdWatchService)->checkAddressServiceability($input);

        return ApiResponse::json($response, 200);
    }

    // called from Thirdwatch internal app
    public function saveCodScoreForAddress()
    {
        $input = Request::all();

        $response = (new Services\ThirdWatchService)->saveCodScoreForAddress($input);

        return ApiResponse::json($response, 200);
    }
}
