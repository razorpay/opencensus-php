<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\OAuth\Token;

class OAuthTokenController extends Controller
{
    protected $auth;

    protected $authservice;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];

        $this->authservice = $this->app['authservice'];
    }

    public function getAll()
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        $input[Token\Entity::MERCHANT_ID] = $merchant->getId();

        $data = $this->authservice->getTokens($input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        $input[Token\Entity::MERCHANT_ID] = $merchant->getId();

        $data = $this->authservice->getToken($id, $input);

        return ApiResponse::json($data);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        $merchant = $this->auth->getMerchant();

        $input[Token\Entity::MERCHANT_ID] = $merchant->getId();

        $data = $this->authservice->revokeToken($id, $input);

        return ApiResponse::json($data);
    }
}
