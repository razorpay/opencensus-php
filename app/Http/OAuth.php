<?php

namespace RZP\Http;

use Razorpay\OAuth\OAuthServer;

class OAuth
{
    protected $server;

    public function __construct()
    {
        $this->server = new OAuthServer();
    }

    public function resolveToken(string $token) : string
    {
        $response = $this->server->authenticateWithBearerToken($token);

        $scopes = $response['scopes'];

        $merchantId = $response['merchant_id'];

        $this->resolveScopes($scopes);

        return $merchantId;
    }

    protected function resolveScopes(array $scopes)
    {
        // Parse the array of scopes

        // Save scopes so endpoints can check against it, if needed

        // check if current route has access for scopes
    }
}