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

    //TODO : once we started using 1cc/check_cod_eligibility api, old api for the same should be deleted
    public function checkCodEligibility()
    {
        $input = Request::all();

        $input['device']['user_agent'] = Request::header('X-User-Agent') ?? Request::header('User-Agent') ?? null;

        $response = (new Services\ThirdWatchService)->checkCodEligibility($input);

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
