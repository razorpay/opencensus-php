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
     * GET /oauth/tokens
     */
    public function getAllTokens()
    {
        $input = Request::all();

        $result = $this->tokenService->getAllTokens($input);

        return ApiResponse::json($result);
    }

    /**
     * GET /oauth/tokens/{$id}
     */
    public function getToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->getToken($id, $input);

        return ApiResponse::json($result);
    }

    /*
     * PATCH /oauth/tokens/{$id}
     */
    public function editToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->editToken($id, $input);

        return ApiResponse::json($result);
    }

    /*
     * PUT oauth/tokens/{$id}/revoke
     */
    public function revokeToken(string $id)
    {
        $input = Request::all();

        $result = $this->tokenService->revokeToken($id, $input);

        return ApiResponse::json($result);
    }
}
