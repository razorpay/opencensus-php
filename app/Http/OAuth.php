<?php

namespace RZP\Http;

use ApiResponse;
use Razorpay\OAuth\OAuthServer;
use Illuminate\Support\Facades\App;
use Razorpay\OAuth\Token\Entity as OAuthToken;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;

class OAuth
{
    const PUBLIC_TOKEN_LENGTH = 29;

    protected $app;

    /**
     * @var BasicAuth
     */
    protected $ba;

    protected $request;

    protected $router;

    protected $trace;

    /**
     * @var string
     */
    protected $publicToken;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app     = $app;
        $this->ba      = $app['basicauth'];
        $this->router  = $app['router'];
        $this->request = $app['request'];
        $this->trace   = $app['trace'];
    }

    /**
     * Checks if the request have an OAuth public token.
     * If request is not found to be having oauth public token, just return false.
     * Otherwise set the $publicToken attribute of this instance(to be used later
     * in authenticate step) and unset key query parameter from request if
     * exists and return true.
     *
     * @return bool
     */
    public function hasOAuthPublicToken(): bool
    {
        $key = $this->ba->getKeyForNonBasicAuthTokens();

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

        if ($isPublicToken === false)
        {
            return false;
        }

        $this->publicToken = $key;

        //
        // If the request was authenticated with key_id sent in the request params
        // we remove the key_id attribute before proceeding
        //
        $this->ba->removeRequestKey('key_id');

        //
        // Set the public_key on BasicAuth
        // We need to do this as public_key gets used to create callback URL
        // which gets sent as query parameter to some of the external calls to
        // bank/gateways.
        //
        $this->ba->setPublicKey($key);

        return $isPublicToken;
    }

    /**
     * Resolve an OAuth access token
     * Assign and verify scopes for the token,
     * returns an array of ID's and mode, if everything checks out
     *
     * @param string $token
     *
     * @return mixed|null ErrorResponse if error, else null
     */
    public function resolveBearerToken(string $token)
    {
        try
        {
            $oauthServer = new OAuthServer($this->app['env']);

            $response = $oauthServer->authenticateWithBearerToken($token);
        }
        catch (\Exception $exception)
        {
            // TODO: Add an API <> OAuth Exception map
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::OAUTH_TOKEN_INVALID
            );

            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID);
        }

        return $this->parseOAuthServerResponse($response);
    }

    /**
     * Resolve and process an OAuth public token
     *
     * @return mixed|null ErrorResponse if error, else null
     * @throws LogicException
     */
    public function resolvePublicToken()
    {
        //
        // If $this->publicToken isn't set by the Authenticate middleware,
        // This is user-created bug
        // Fail with a LogicException
        //
        if (isset($this->publicToken) === false)
        {
            throw new LogicException('OAuth: publicToken property was not set in the Authenticate middleware');
        }

        try
        {
            $oauthServer = new OAuthServer($this->app['env']);

            $response = $oauthServer->authenticateWithPublicToken($this->publicToken);
        }
        catch (\Exception $exception)
        {
            // TODO: Add an API <> OAuth Exception map
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::OAUTH_TOKEN_INVALID
            );

            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID);
        }

        return $this->parseOAuthServerResponse($response);
    }

    /**
     * Parse the OAuth server response received.
     * Returns an error object, if there is an error.
     * Returns null otherwise.
     *
     * @param array $response
     *
     * @return array
     */
    protected function parseOAuthServerResponse(array $response)
    {
        $tokenScopes = $response[OAuthToken::SCOPES];

        if ($this->areScopesAllowed($tokenScopes) === false)
        {
            return ApiResponse::oauthInvalidScope();
        }

        $mode = $response[OAuthToken::MODE];

        // Sets the mode for the request, and database connection
        $this->ba->setModeAndDbConnection($mode);

        //
        // Set merchant for the current request
        // TODO: Move this to a common auth class
        //
        $this->ba->setMerchantById($response[OAuthToken::MERCHANT_ID]);

        //
        // Public key is used to generate the callback URL parameter that is
        // being sent with the payment create request to the gateway.
        //
        $publicKey = 'rzp_' . $mode . '_oauth_' . $response[OAuthToken::PUBLIC_TOKEN];

        $this->ba->setPublicKey($publicKey);

        try
        {
            $this->ba->checkMerchantActivatedForLive();
        }
        catch (Exception\LogicException $e)
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED);
        }

        // Sets the identifiers that are sent in trace logs
        $this->ba->setAccessTokenId($response[OAuthToken::ID]);
        $this->ba->setOAuthClientId($response[OAuthToken::CLIENT_ID]);
        $this->ba->setOAuthApplicationId($response[OAuthToken::APPLICATION][OAuthToken::ID]);
    }

    /**
     * Check if a token has enough scopes to access a route
     *
     * @param array $tokenScopes
     *
     * @return bool
     */
    protected function areScopesAllowed(array $tokenScopes): bool
    {
        $route = $this->router->currentRouteName();

        //
        // Fetch the scopes defined for the current route, including defaults
        // like 'read_only' and 'read_write'
        //
        $routeScopes = OAuthScopes::getScopesForRoute($route);

        //
        // Atleast one of the scopes defined for the route should have been
        // attached to the token.
        //
        $commonScopes = array_intersect($routeScopes, $tokenScopes);

        return (count($commonScopes) > 0);
    }

    /**
     * TODO: Parse exceptions thrown from the OAuth package
     * and throw corresponding exceptions from API
     */
    protected function parseOAuthException()
    {
        // TODO: Add an API <> OAuth Exception map
    }
}
