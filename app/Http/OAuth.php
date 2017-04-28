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

    public function resolveToken(string $token) : string
    {
        $response = $this->server->authenticateWithBearerToken($token);
        $scopes = (array) $response['scopes'];

        $merchantId = $response['merchant_id'];

        $this->resolveScopes($scopes);

        return $merchantId;
    }

    protected function resolveScopes(array $scopes)
    {
        // check if current route has access for scopes

        // Save scopes so endpoints can check against it, if needed
        $this->ba->withScopes($scopes);
    }
}