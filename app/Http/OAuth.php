<?php

namespace RZP\Http;

use ApiResponse;

use Illuminate\Http\Request;
use Razorpay\OAuth\OAuthServer;
use RZP\Http\BasicAuth\BasicAuth;
use Illuminate\Support\Facades\App;

class OAuth
{
    const PUBLIC_TOKEN_LENGTH = 29;

    protected $server;

    /**
     * @var BasicAuth
     */
    protected $ba;

    protected $request;

    public function __construct(Request $request)
    {
        $app = App::getFacadeRoot();

        $this->ba = $app['basicauth'];

        $this->server = new OAuthServer();

        $this->request = $request;
    }

    /**
     * Check if the request have an OAuth public token
     *
     * @return bool
     */
    public function hasOAuthPublicToken()
    {
        $request = $this->request;

        $key = $request->input('key_id') ?? $request->getUser();

        //
        // If the key was empty or null, return false and allow
        // the BasicAuth class to validate the key and throw the correct
        // Error in response
        //
        if (empty($key) === true)
        {
            return false;
        }

        // Check for key length and the '_oauth_' sub-string
        $isPublicToken = ((strlen($key) === self::PUBLIC_TOKEN_LENGTH) and
                          (substr($key, 8, 7) === '_oauth_'));

        return $isPublicToken;
    }

    /**
     * Resolve an OAuth access token
     * Assign and verify scopes for the token,
     * returns an array of ID's and mode, if everything checks out
     *
     * @param string $token
     *
     * @return array
     */
    public function resolveToken(string $token)
    {
        $response = $this->server->authenticateWithBearerToken($token);

        // Error
        if (empty($response) === true)
        {
            return null;
        }

        $scopes = (array) $response['scopes'];

        $this->resolveScopes($scopes);

        $merchantId = $response['merchant_id'];

        $tokenId = $response['id'];

        $clientId = $response['client_id'];

        $mode = $response['mode'];

        return [$merchantId, $tokenId, $clientId, $mode];
    }

    public function resolvePublicToken(string $token)
    {
        $response = $this->server->authenticateWithBearerToken($token);

        // Error
        if (empty($response) === true)
        {
            return null;
        }
    }

    protected function resolveScopes(array $tokenScopes)
    {
        // Save scopes so endpoints can check against it, if needed
        $this->ba->withScopes($tokenScopes);
    }
}
