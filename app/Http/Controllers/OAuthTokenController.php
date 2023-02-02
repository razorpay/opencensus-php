<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;

use RZP\Trace\Tracer;
use RZP\Models\OAuthToken;
use RZP\Constants\HyperTrace;

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

        $data = Tracer::inspan(['name' => HyperTrace::GET_OAUTH_TOKENS], function () use($input, $merchantId) {

            return $this->authservice->getTokens($input, $merchantId);
        });

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

        $data = Tracer::inspan(['name' => HyperTrace::REVOKE_OAUTH_TOKEN], function () use($id, $input, $merchantId) {

            return $this->authservice->revokeToken($id, $input, $merchantId);
        });

        return ApiResponse::json($data);
    }

    public function create()
    {
        $entity = Tracer::inspan(['name' => HyperTrace::CREATE_OAUTH_TOKEN], function () {

            return $this->service()->create();
        });

        return $entity;
    }
}
