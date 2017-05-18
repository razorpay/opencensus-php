<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\OAuth\Token\Service as TokenService;

class OAuthTokenController extends Controller
{
    /**
     * @var TokenService
     */
    protected $tokenService;

    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->tokenService = new TokenService;
    }

    public function getTokens()
    {
        $input = Request::all();

        //
        // OAuth Services are sent a single array of data
        // This is to allow for simple .proto definitions
        // when we move the OAuth module to a gRPC implementation
        //
        $input['merchant_id'] = $this->merchant->getId();

        $result = $this->tokenService->getAllTokens($input);

        return ApiResponse::json($result);
    }

    public function getToken(string $id)
    {
        $input = Request::all();

        $input['id'] = $id;

        $input['merchant_id'] = $this->merchant->getId();

        $result = $this->tokenService->getToken($input);

        return ApiResponse::json($result);
    }

    public function updateToken(string $id)
    {
        $input = Request::all();

        $input['id'] = $id;

        $input['merchant_id'] = $this->merchant->getId();

        $result = $this->tokenService->editToken($input);

        return ApiResponse::json($result);
    }

    public function revokeToken(string $id)
    {
        $input = Request::all();

        $input['id'] = $id;

        $input['merchant_id'] = $this->merchant->getId();

        $result = $this->tokenService->revokeToken($input);

        return ApiResponse::json($result);
    }
}
