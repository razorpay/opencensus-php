<?php

namespace RZP\Http;

use Illuminate\Support\Facades\App;
use Razorpay\OAuth\OAuthServer;
use RZP\Http\BasicAuth\BasicAuth;

class OAuth
{
    protected $server;

    /**
     * @var BasicAuth
     */
    protected $ba;


    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->ba = $app['basicauth'];

        $this->server = new OAuthServer();
    }

    /**
     * Resolve an OAuth access token
     * Assign and verify scopes for the token,
     * returns merchant ID if everything checks out
     *
     * @param string $token
     *
     * @return string
     */
    public function resolveToken(string $token) : string
    {
        $response = $this->server->authenticateWithBearerToken($token);

        $scopes = json_decode($response['scopes'], true);

        $this->resolveScopes($scopes);

        $merchantId = $response['merchant_id'];

        return $merchantId;
    }

    protected function resolveScopes(array $tokenScopes)
    {
        // Save scopes so endpoints can check against it, if needed
        $this->ba->withScopes($tokenScopes);
    }
}