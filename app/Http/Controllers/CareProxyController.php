<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class CareProxyController extends Controller
{

    const CHECK_ELIGIBILITY = 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility';

    const MERCHANT_ROUTES = [
        self::CHECK_ELIGIBILITY,
    ];

    public function postDashboardProxyRequest($path)
    {
        $this->validatePathForDashboardProxyRequest($path);

        $input = Request::all();

        $response = $this->app['care_service']->dashboardProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    protected function validatePathForDashboardProxyRequest($path)
    {
        if (in_array($path, self::MERCHANT_ROUTES) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }
}
