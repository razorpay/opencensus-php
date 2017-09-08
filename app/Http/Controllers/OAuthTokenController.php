<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;

class OAuthTokenController extends Controller
{
    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $auth;

    /**
     * @var \RZP\Services\AuthService
     */
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

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->getTokens($input, $merchantId);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->getToken($id, $input, $merchantId);

        return ApiResponse::json($data);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        $merchantId = $this->auth->getMerchantId();

        $data = $this->authservice->revokeToken($id, $input, $merchantId);

        return ApiResponse::json($data);
    }
}
