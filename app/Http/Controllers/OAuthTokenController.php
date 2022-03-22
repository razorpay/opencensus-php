<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;

use RZP\Models\OAuthToken;

class OAuthTokenController extends Controller
{
    protected $service = OAuthToken\Service::class;

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

    public function create()
    {
        $entity = $this->service()->create();

        return $entity;
    }

    /**
     * This route is used to generate OAuth token for Apple Watch without authorization step
     * (Since app is internal)
     */
    public function createForAppleWatch()
    {
        $input = Request::all();

        $user = $this->auth->getUser();

        $merchant = $this->auth->getMerchant();

        $mode = $this->auth->getMode();

        $response = $this->service()->createForAppleWatch($input,$user,$merchant,$mode);

        return ApiResponse::json($response);
    }
}
