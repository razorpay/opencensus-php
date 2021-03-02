<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class CareProxyController extends Controller
{

    //proxy
    const CHECK_ELIGIBILITY = 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility';
    const GET_SLOTS         = 'twirp/rzp.care.callback.v1.CallbackService/GetSlots';
    const CREATE_CALLBACK   = 'twirp/rzp.care.callback.v1.CallbackService/CreateCallback';
    const GET_CALLBACK      = 'twirp/rzp.care.callback.v1.CallbackService/GetCallback';

    //cron
    const INIT_SLOTS = 'twirp/rzp.care.callback.v1.CallbackService/InitSlots';

    const MERCHANT_ROUTES = [
        self::CHECK_ELIGIBILITY,
        self::GET_SLOTS,
        self::CREATE_CALLBACK,
        self::GET_CALLBACK,
    ];

    const CRON_ROUTES = [
        self::INIT_SLOTS,
    ];

    public function postDashboardProxyRequest($path)
    {
        $this->validatePathForRequest(self::MERCHANT_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['care_service']->dashboardProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    public function postCronProxyRequest($path)
    {
        $this->validatePathForRequest(self::CRON_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['care_service']->cronProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    protected function validatePathForRequest($routes, $path)
    {
        if (in_array($path, $routes) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
}
