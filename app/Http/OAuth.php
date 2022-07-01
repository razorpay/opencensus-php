<?php

namespace RZP\Http;

use ApiResponse;
use Razorpay\OAuth\OAuthServer;
use RZP\Http\Edge\PassportUtil;
use Illuminate\Support\Facades\App;
use Razorpay\Edge\Passport\Passport;
use Razorpay\OAuth\Application\Repository;
use Razorpay\OAuth\Token\Entity as OAuthToken;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\BasicAuth\Type as AuthType;

class OAuth
{
    use OAuthCache;
    const PUBLIC_TOKEN_LENGTH = 29;
    const PUBLIC_KEY = 'public_key';
    const ID = 'id';

    protected $app;

    /**
     * @var BasicAuth
     */
    protected $ba;

    protected $request;

    protected $router;

    protected $trace;

    protected $cache;

    /**
     * @var string
     */
    protected $publicToken;

    /**
     * @var string
     */
    protected $accountId;

    /**
     * Used to access passport related information stored during PreAuthenticate.
     * @var Passport
     */
    protected $passport;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app     = $app;
        $this->ba      = $app['basicauth'];
        $this->router  = $app['router'];
        $this->request = $app['request'];
        $this->trace   = $app['trace'];
        // use the throttle cache for storing the tokens data
        $cacheStore    = $this->app['config']->get('cache.throttle');
        $this->cache   = $app['cache']->store($cacheStore);
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

        $isPublicToken = $isPublicTokenWithAccountId = false;

        $accountId = null;

        // rzp_test_oauth_Dm68K5swlymBVD-acc_Dq2gQrRkp6AO2A
        $keyRegex = '/^(rzp_(test|live)_oauth_[a-zA-Z0-9]{14})[-](acc_[a-zA-Z0-9]{14})$/';

        $validCallbackKey = (preg_match($keyRegex, $key, $matches) === 1);

        if ($validCallbackKey === true)
        {
            $this->trace->info(TraceCode::OAUTH_TOKEN_WITH_PARTNER_ACCOUNT, ['key' => $key]);

            $key       = $matches[1];
            $accountId = $matches[3];

            $isPublicTokenWithAccountId = true;
        }
        else
        {
            // Check for key length and the '_oauth_' sub-string
            $isPublicToken = ((strlen($key) === self::PUBLIC_TOKEN_LENGTH) and
                              (substr($key, 8, 7) === '_oauth_'));
        }

        if (($isPublicToken === false) and
            ($isPublicTokenWithAccountId === false))
        {
            return false;
        }

        $this->publicToken = $key;
        $this->accountId   = $accountId;

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

        return ($isPublicToken or $isPublicTokenWithAccountId);
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
        $response = [];
        $storeCache = false;

        try
        {
            $cacheKey = $this->getCacheKey($token);

            //
            // When a request is authenticated, only bearer token is available
            // So the cacheTag needs to be the hash of the bearer token itself.
            //
            $cacheTags = $this->getCacheTagsForToken($token);
            $response  = $this->cache->tags($cacheTags)->get($cacheKey) ?? [];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::TOKEN_CACHE_READ_ERROR
            );
        }

        try
        {
            if (empty($response) === true)
            {
                $oauthServer = new OAuthServer($this->app['env']);

                $response = $oauthServer->authenticateWithBearerToken($token);

                $storeCache = true;
            }

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

        try
        {
            if ($storeCache === true)
            {
                list($ttl, $key) = $this->getCacheInfo($token);

                $this->cache->tags($cacheTags)->put($key, $response, $ttl);

                // Similarly, when a token is being revoked the only attribute available is the ID of the token.
                // Hence we use the ID as key to cache the tokentag
                $tokenTag = $this->getCacheTagsForTokenId($response['id']);
                $this->cache->tags($tokenTag)->put($response['id'], $key);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::TOKEN_CACHE_STORE_ERROR
            );
        }

        return $this->parseOAuthServerResponse($response, AuthType::PRIVATE_AUTH);
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

        $response = [];
        $storeCache = false;

        try
        {
            $token = $this->publicToken;
            $cacheKey = $this->getCacheKey($token);

            // When a request is authenticated, only bearer token is available
            // So the cacheTag needs to be the hash of the bearer token itself.
            $cacheTags = $this->getCacheTagsForToken($token);
            $response = $this->cache->tags($cacheTags)->get($cacheKey) ?? [];
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::TOKEN_CACHE_READ_ERROR
            );
        }

        try
        {
            if (empty($response) === true)
            {
                $oauthServer = new OAuthServer($this->app['env']);

                $response = $oauthServer->authenticateWithPublicToken($this->publicToken);

                $storeCache = true;
            }

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

        try
        {

            if ($storeCache === true)
            {
                list($ttl, $key) = $this->getCacheInfo($this->publicToken);

                $this->cache->tags($cacheTags)->put($key, $response, $ttl);

                // Similarly, when a token is being revoked the only attribute available is the ID of the token.
                // Hence we use the ID as key to cache the tokentag
                $tokenTag = $this->getCacheTagsForTokenId($response['id']);
                $this->cache->tags($tokenTag)->put($response['id'], $key);
            }

        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::INFO,
                TraceCode::TOKEN_CACHE_STORE_ERROR
            );
        }

        return $this->parseOAuthServerResponse($response, AuthType::PUBLIC_AUTH);
    }

    /**
     * Parse the OAuth server response received.
     * Returns an error object, if there is an error.
     * Returns null otherwise.
     *
     * @param array       $response
     * @param string      $auth
     *
     * @return mixed (error array/ void)
     * @throws Exception\BadRequestException
     */
    protected function parseOAuthServerResponse(array $response, string $auth)
    {
        $tokenScopes = $response[OAuthToken::SCOPES];

        if ($this->areScopesAllowed($tokenScopes) === false)
        {
            return ApiResponse::oauthInvalidScope();
        }

        $mode = $response[OAuthToken::MODE];

        //
        // Public key is used to generate the callback URL parameter that is
        // being sent with the payment create request to the gateway.
        //
        $publicKey = 'rzp_' . $mode . '_oauth_' . $response[OAuthToken::PUBLIC_TOKEN];

        $this->ba->oauthPublicTokenAuth($publicKey, $auth);

        // Sets the mode for the request, and database connection
        $this->ba->authCreds->setModeAndDbConnection($mode);

        $merchantId = $response[OAuthToken::MERCHANT_ID];

        //
        // Set merchant for the current request
        // TODO: Move this to a common auth class
        //
        $this->ba->setMerchantById($merchantId);
        $userId = null;

        try
        {
            $userId = $response[OAuthToken::USER_ID];

            //
            // Set user for the current request
            // TODO: Move this to a common auth class
            //
            $this->ba->setUserById($userId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::USER_CONTEXT_NOT_PRESENT_FOR_OAUTH_REQUEST, [OAuthToken::MERCHANT_ID => $merchantId, 'error' => $ex]);
        }


        try
        {
            $this->ba->authCreds->checkMerchantActivatedForLive();
        }
        catch (Exception\LogicException $e)
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED);
        }

        $error = $this->handleAccountAuthIfApplicable();

        if ($error !== null)
        {
            return $error;
        }

        // Sets the identifiers that are sent in trace logs
        $this->ba->setAccessTokenId($response[OAuthToken::ID]);
        $this->ba->setOAuthClientId($response[OAuthToken::CLIENT_ID]);
        $this->ba->setOAuthApplicationId($response[OAuthToken::APPLICATION_ID]);
        $this->ba->setUserRoleWithUserIdAndMerchantId($merchantId, $userId);
        // not sent in trace logs
        $this->ba->setTokenScopes($tokenScopes);

        $isRazorpayXExclusiveRoute = $this->isBankingRoute();

        $isApplicationAllowedForBankingRoutes = (new Feature\Service())->checkFeatureEnabled(Feature\Constants::APPLICATION,
                                                            $response[OAuthToken::APPLICATION_ID],
                                                Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH)['status'];

        if ($isRazorpayXExclusiveRoute === true and $isApplicationAllowedForBankingRoutes === false)
        {
            return ApiResponse::unauthorizedOauthAccessToRazorpayX();
        }

        //Fetches partnerMerchantId from applicationId and adds to ba.
        $application = (new Repository())->findOrFail($response[OAuthToken::APPLICATION_ID]);
        $this->ba->setPartnerMerchantId($application->getMerchantId());

        $this->setPassportMetadata($response, $application->getMerchantId(), $auth, $tokenScopes);

        return null;
    }

    private function setPassportMetadata(array $response, string $mid, string $auth, $tokenScopes)
    {
        $this->ba->setPassportOAuthClaims(
            BasicAuth::PASSPORT_OAUTH_OWNER_TYPE_MERCHANT,
            $response[OAuthToken::MERCHANT_ID],
            $response[OAuthToken::CLIENT_ID],
            $response[OAuthToken::APPLICATION_ID],
            $response[OAuthToken::CLIENT_ENVIRONMENT]
        );

        $this->ba->setPassportConsumerClaims(
            BasicAuth::PASSPORT_CONSUMER_TYPE_MERCHANT,
            $mid,
            $auth === AuthType::PRIVATE_AUTH // The function parseOAuthServerResponse is called for both public and private flows.
        );

        $this->ba->setPassportRoles(preg_filter('/^/', 'oauth::scope::', $tokenScopes));
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

    public function handleAccountAuthIfApplicable()
    {
        $partnerMerchant = $this->ba->authCreds->getMerchant();

        if ((empty($partnerMerchant) === true) or
            ($partnerMerchant->isPartner() === false) or
            ($partnerMerchant->isPurePlatformPartner() === true))
        {
            return null;
        }

        $accountId = $this->accountId ?: $this->request->headers->get(RequestHeader::X_RAZORPAY_ACCOUNT);

        $accountId =  $accountId ?: $this->request->query(BasicAuth::ACCOUNT_ID);

        if (empty($accountId) === true)
        {
            return null;
        }

        // remove account Id in query Params, if sent.
        $this->request->query->remove(BasicAuth::ACCOUNT_ID);

        if ($partnerMerchant->isFeatureEnabled(Feature\Constants::AGGREGATOR_OAUTH_CLIENT) === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED);
        }

        $error = $this->checkAndSetAccountId($accountId);

        if ($error !== null)
        {
            return $error;
        }
    }

    public function checkAndSetAccountId(string $accountId)
    {
        if ($this->ba->verifyAccountId($accountId) === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
        }

        // Set account_id in creds
        $this->ba->authCreds->creds[BasicAuth::ACCOUNT_ID] = $accountId;

        // Set callback_key
        $callbackKey = $this->ba->getCallbackKeyWithAccountId($accountId);
        $this->ba->authCreds->setPublicKey($callbackKey);

        $accountId = $this->ba->getAccountId();

        /** @var Merchant\Entity $account */
        $account = app('repo')->merchant->find($accountId);

        if (empty($account) === true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
        }

        $partnerMid = $this->ba->authCreds->getMerchant()->getId();

        $this->ba->setPartnerMerchantId($partnerMid);

        try
        {
            $this->ba->authCreds->setAndCheckMerchantActivatedForLive($account);
        }
        catch (LogicException $e)
        {
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED);
        }

        // merchantId should now have been set to the sub-merchant account's ID
        $accountId = $this->ba->authCreds->getMerchant()->getId();

        if ((new Merchant\Core)->isMerchantManagedByPartner($accountId, $partnerMid) === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }
    }

    public function isBankingRoute(): bool
    {
        $route = $this->router->currentRouteName();

        //
        // Fetch the scopes defined for the current route, including defaults
        // like 'read_only' and 'read_write'
        //
        $routeScopes = OAuthScopes::getScopesForRoute($route);

        return (count(array_diff($routeScopes, OAuthScopes::RAZORPAY_X_SCOPES)) == 0);
    }
}
