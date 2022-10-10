<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;

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

        try
        {
            $response = (new Services\ThirdWatchService)->checkCodEligibility($input);

            return ApiResponse::json($response, 200);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    // called from Thirdwatch internal app
    public function saveCodScoreForAddress()
    {
        $input = Request::all();

        $response = (new Services\ThirdWatchService)->saveCodScoreForAddress($input);

        return ApiResponse::json($response, 200);
    }
}
