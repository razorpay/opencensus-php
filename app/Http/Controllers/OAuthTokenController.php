<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\OAuth\Token\Service as TokenService;

class OAuthTokenController extends Controller
{
    protected $tokenService;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->tokenService = new TokenService;
    }

    public function getTokens()
    {
        $input = Request::all();

        $result = $this->tokenService->getAllTokens($input);

        return ApiResponse::json($result);
    }

    public function getToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->getToken($id, $input);

        return ApiResponse::json($result);
    }

    public function udpateToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->editToken($id, $input);

        return ApiResponse::json($result);
    }

    public function revokeToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->revokeToken($id, $input);

        return ApiResponse::json($result);
    }
}
