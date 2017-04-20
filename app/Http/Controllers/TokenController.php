<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\OAuth\Token\Service as TokenService;

class TokenController extends Controller
{
    protected $tokenService;

    public function __construct()
    {
        parent::__construct();

        $this->tokenService = new TokenService;
    }

    /**
     * POST /oauth/token/request
     *
     * Allows an application to request user authorization
     * Returned with a request token
     */
    public function requestToken()
    {
        $input = Request::all();

        $result = $this->tokenService->newRequestToken($input);

        return ApiResponse::json($result);
    }

    /**
     * POST /oauth/token/access
     *
     * Allows an application to obtain access token
     * in exchange of request token
     */
    public function accessToken()
    {
        $input = Request::all();

        $result = $this->tokenService->accessToken($input);

        return ApiResponse::json($result);
    }

    /*
     * POST oauth/token/revoke
     *
     * Allows an application to revoke an issued token
     * Once invalidated, the new token may be issued via request api.
     */
    public function revokeToken()
    {
        $input = Request::all();

        $result = $this->tokenService->revokeToken($input);

        return ApiResponse::json($result);
    }
}
