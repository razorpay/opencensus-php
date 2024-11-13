<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use Razorpay\Edge\Passport\Passport;
use RZP\Constants\HyperTrace;
use Illuminate\Support\Str;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Http\BasicAuth\ClientAuthCreds;
use RZP\Http\BasicAuth\Type;
use RZP\Http\Edge\Metric;
use RZP\Http\Edge\PassportUtil;
use Illuminate\Foundation\Application;
use RZP\Http\RequestContextV2;
use RZP\Models\Merchant\Entity;
use Symfony\Component\HttpFoundation\Response;

use ApiResponse;
use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Http\Response\Header;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Edge\PostAuthenticate;
use RZP\Exception\BadRequestException;
use Razorpay\OAuth\Application\Repository;
use Razorpay\OAuth\Token\Entity as OAuthToken;

class Authenticate
{
    // Lists of metrics
    const METRIC_AUTH_HANDLE_MILLISECONDS = 'authenticate_handle_milliseconds.histogram';
    const METRIC_AUTH_HANDLE_USING_PASSPORT_MILLISECONDS = 'authenticate_handle_using_passport_milliseconds.histogram';

    const PARTNER       = 'partner';
    const OAUTH         = 'oauth';
    const KEY           = 'key';
    const KEY_ID        = 'key_id';
    const MERCHANT_ID   = 'merchant_id';
    const ACCOUNT_ID    = 'account_id';
    const ROUTE         = 'route';
    const HOST          = 'host';
    const STATUS        = 'status';
    const AUTH          = 'auth';
    const PUBLIC_KEY    = 'public_key';
    const MODE          = 'mode';

    const AUTH_FLOW     = 'auth_flow';
    const PASSPORT_AUTH = 'passport_auth';

    const PASSPORT_AUTH_TYPE   = 'passport_auth_type';
    const APP_MERCHANT_ID      = 'app_merchant_id';
    const PASSPORT_CONSUMER_ID = 'passport_consumer_id';
    const PARTNER_MERCHANT_ID  = 'partner_merchant_id';
    const ACCOUNT_ID_SOURCE    = 'account_id_source';
    const APP_NAME             = 'app_name';
    const SECRET               = 'secret';
    const ROUTE_TYPE           = 'route_type';
    const X_ENTITY_ID_QUERY_KEY  = 'x_entity_id';

    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * @var OAuth
     */
    protected $oauth;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Used to access passport related information stored during DecodePassportJWT middleware.
     * @var RequestContextV2
     */
    protected $requestContext;

    /**
     * @var Passport|null
     */
    protected $passport;

    /**
     * @var PassportUtil|null
     */
    protected $passportUtil;

    /**
     * Trace instance used for tracing
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    protected $isPartnerAuth = false;

    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    /**
     * Route type
     * @var string
     */
    protected $routeType;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $this->app['basicauth'];

        $this->requestContext = $app['request.ctx.v2'];

        $this->router = $this->app['router'];

        $this->trace = $this->app['trace'];

        $this->oauth = new OAuth();

        $this->passport = $this->requestContext->passport;

        $this->route = $this->app['api.route'];

        $this->routeType = $this->route->getRouteType();

        $this->passportUtil = empty($this->passport) ? null : new PassportUtil($this->passport);
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request  $request
     * @param Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $startAt = millitime();
        $endAt   = null;

        $span = Tracer::startSpan(['name' => self::METRIC_AUTH_HANDLE_MILLISECONDS]);
        $scope = Tracer::withSpan($span);

        $route = $this->router->currentRouteName();

        $this->ba->init();

        // check if the request should be authenticated using Edge passport
        if ( $this->requestContext->shouldAuthenticateUsingPassport || $this->shouldUsePassportWithInternalAuth($route,$request) )
        {
            $this->isPartnerAuth = ($this->passport->consumer->type == self::PARTNER);
            $passportAuthType = $this->getPassportAuthType();

            $this->trace->debug(TraceCode::AUTHENTICATING_USING_PASSPORT,
                [
                    self::PUBLIC_KEY         => $this->passport->credential->publicKey,
                    self::MERCHANT_ID        => $this->passport->consumer->id,
                    self::ACCOUNT_ID         => $this->passportUtil->getAccountId(),
                    self::ROUTE              => $route,
                    self::PASSPORT_AUTH_TYPE => $passportAuthType,
                    self::APP_NAME           => $this->ba->getInternalApp(),
                    self::ROUTE_TYPE         => $this->routeType
                ]
            );

            // in case of app auth with edge passport. after validation of internal auth creds it's similar to private/public auth
            // so resetting internal app name in order to avoid any conflict in further processing.
            $this->ba->setInternalApp(null);

            $ret = Tracer::inspan(['name' => HyperTrace::AUTHENTICATE_USING_PASSPORT], function () use($passportAuthType) {
                return (empty($this->passport->oauth) ? $this->setBasicAuthContextsFromPassport($passportAuthType) : $this->setOauthContextsFromPassport($passportAuthType));
            });

            // few business logic still use ba passport to fetch roles etc, hence set it
            $this->ba->setPassport($this->passport);

            // add histogram only for requests authenticated with passport
            app()->trace->histogram(
                self::METRIC_AUTH_HANDLE_USING_PASSPORT_MILLISECONDS,
                millitime() - $startAt,
                $this->ba->getRequestMetricDimensions());
        }
        else
        {
            $bearerToken = $this->getBearerTokenFromHeaders($request);
            //
            // If the request was sent with Bearer auth (OAuth),
            // authenticate with the access token, else go for the
            // otherwise existing key-secret flow
            //
            if (empty($bearerToken) === false)
            {
                $ret = Tracer::inspan(['name' => HyperTrace::AUTHENTICATE_BEARER_AUTH], function () use ($route, $bearerToken) {
                        return $this->authenticateBearerAuth($route, $bearerToken);
                    });
            }
            else
            {
                $ret = Tracer::inspan(['name' => HyperTrace::AUTHENTICATE_BASIC_AUTH], function () use ($route) {
                        return $this->authenticateBasicAuth($route);
                    });
            }

            // end middleware latency histogram
            $endAt = millitime();

            // Any not null $ret (e.g. 401, 403 etc) means the request was not authenticated.
            // At the same time a null $ret, in case of direct route still means request was not authenticated(read- not required).
            $authenticated = (($ret === null) and ($this->ba->isDirectAuth() === false) and ($this->ba->isPublicAuth() === false));

            (new PostAuthenticate)->handle($authenticated, $request);
        }

        $scope->close();

        $endAt = empty($endAt) ? millitime() : $endAt;
        app()->trace->histogram(
            self::METRIC_AUTH_HANDLE_MILLISECONDS,
            $endAt - $startAt,
            $this->ba->getRequestMetricDimensions());

        // add counter only for recognised auth types at edge
        $passportAuthType = empty($this->passportUtil) ? null : $this->passportUtil->getAuthTypeFromPassport();
        if (! empty($passportAuthType))
        {
            $this->trace->count(Metric::AUTHENTICATED_USING_PASSPORT_TOTAL, [
                self::ROUTE              => $route,
                self::PASSPORT_AUTH      => $this->requestContext->shouldAuthenticateUsingPassport,
                self::PASSPORT_AUTH_TYPE => $passportAuthType,
                self::HOST               => $request->getHttpHost(),
                self::ROUTE_TYPE         => $this->routeType
            ]);
        }

        if (($this->passport !== null) and
            ($this->passport->consumer !== null) and
            ($this->passport->consumer->type !== null) and
            ($this->passport->consumer->id !== null))
        {
            Tracer::addAttribute($this->passport->consumer->type, $this->passport->consumer->id);
        }

        // Non-null value indicates failure flow
        if ($ret !== null)
        {
            return $this->postHandle($ret);
        }

        $ret = $next($request);

        return $this->postHandle($ret);
    }

    /**
     * Authenticate the request with Basic auth
     * non-null return value indicates a failure
     *
     * @param string $route
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    protected function authenticateBasicAuth(string $route)
    {
        $ret = null;

        switch ($this->routeType) {
            case Route::INTERNAL:
            case Route::ADMIN:
                $ret = $this->ba->appAuth();
                break;
            case Route::PRIVATE:
                $ret = Tracer::inspan(['name' => HyperTrace::AUTHENTICATE_PRIVATE_ROUTE_PRIVATE_AUTH], function () {
                    return $this->ba->privateAuth();
                });
                break;
            case Route::P2P_PRIVATE:
                $ret = $this->ba->privateAuth();
                break;
            case Route::PUBLIC:
                // For public routes, OAuth sends a public_token using BasicAuth
                // We check here if the key is an OAuth public token and
                // process accordingly.
                //
                if ($this->oauth->hasOAuthPublicToken() === true) {
                    $ret = $this->authenticateOAuthPublicToken();
                } else {
                    // Process via BasicAuth
                    $ret = $this->ba->publicAuth();
                }
                break;
            case Route::P2P_PUBLIC:
                $ret = $this->ba->p2pPublicAuth();
                break;
            case ROUTE::PUBLIC_CALLBACK:
                if ($this->ba->hasPartnerAuthCallbackKey() === true) {
                    $ret = $this->ba->handlePartnerAuthOnPublicCallback();
                } else if ($this->oauth->hasOAuthPublicToken() === true) {
                    $ret = $this->authenticateOAuthPublicToken();
                } else {
                    $ret = $this->ba->publicCallbackAuth();
                }
                break;
            case ROUTE::PROXY:
                $ret = $this->ba->proxyAuth();
                break;
            case Route::DEVICE:
                $ret = $this->ba->deviceAuth();
                break;
            case Route::P2P_DEVICE:
                $ret = $this->ba->p2pDeviceAuth();
                break;
            case ROUTE::DIRECT:
            case ROUTE::P2P_DIRECT:
                $ret = $this->ba->directAuth();
                break;
            default:
                $ret = ApiResponse::routeNotFound();
                break;
        }

        return $ret;
    }

    /**
     * Authenticate the request with a OAuth token
     *
     * @param string $route
     * @param string $bearerToken
     *
     * @return mixed|null ErrorResponse if error, else null
     */
    protected function authenticateBearerAuth(string $route, string $bearerToken)
    {
        //
        // Only private auth endpoints may be accessed
        // with OAuth bearer tokens
        //
        // A separate array is maintained for routes which need to accessed via OAuth but not Basic Auth
        $bearerAuthRoutes = array_merge(Route::$private,Route::OAUTH_SPECIFIC_ROUTES);
        if (in_array($route, $bearerAuthRoutes, true) === false)
        {
            return ApiResponse::routeNotFound();
        }

        return $this->oauth->resolveBearerToken($bearerToken);
    }

    /**
     * Handle authentication for public route that have an
     * OAuth public token set
     * Sample token: rzp_test_oauth_8P3XVPteKu4igS
     *
     * @return mixed|null ErrorResponse if error, else null
     * @throws \RZP\Exception\LogicException
     */
    protected function authenticateOAuthPublicToken()
    {
        return $this->oauth->resolvePublicToken();
    }

    /**
     * @param $request
     *
     * @return string|null
     */
    private function getBearerTokenFromHeaders($request)
    {
        //
        // After a fix in infra code we will use following to get bearer token:
        // $request->getBearerToken();
        //
        // But for now following are the issues:
        // - With apache, 'Authorization' headers is missing unless specific
        //   configuration. So for that we started using getAllHeaders(). This
        //   method is available when PHP is running with apache and so we have
        //   added a polyfill utility method in case on local someone is using
        //   Nginx.
        // - Now with tests, it's not actually an HTTP request during request
        //   response flow. Framework forms a Symfony request object and directly
        //   starts from framework kernel's instantiation(by passing actual HTTP flow).
        //   And so extra $_SERVER headers are missing. So in tests using $request's
        //   bearerToken() method.
        //

        if ($this->app->runningUnitTests() === true)
        {
            // Returns string or null
            return $request->bearerToken();
        }

        $authHeader = getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? null;

        $bearerToken = '';

        if ($authHeader !== null)
        {
            if (Str::startsWith($authHeader, 'Bearer '))
            {
                $bearerToken = Str::substr($authHeader, 7);
            }
        }

        return $bearerToken;
    }

    /**
     * Post part of this middleware.
     * @param  Response $res
     * @return void
     * @return Response
     */
    protected function postHandle(Response $res): Response
    {
        // Todo: For sometime, returns this header always.
        // To undo and return only for qa env, where it is used for tests.

        /** @var \RZP\Http\RequestContextV2 $reqCtx */
        $reqCtx = $this->app['request.ctx.v2'];
        if ($reqCtx->hasPassportJwt === true)
        {
            $res->headers->set(Header::X_PASSPORT_ATTRS_MISMATCH, (int) $reqCtx->passportAttrsMismatch);
        }

        // add all relevant details in one log on authenticate middleware
        // log only for private auth and public auth for now
        // proxy auth also sets type as private hence ignore it
        // isKeylessPublicAuth checks for public auth by itself
        if ($this->ba->isStrictPrivateAuth() || $this->ba->isStrictPublicAuth()) {
            $this->trace->debug(TraceCode::RESPONSE_STATUS_AT_AUTH_MIDDLEWARE, [
                self::PUBLIC_KEY          => $this->ba->getPublicKey(),
                self::MERCHANT_ID         => $this->ba->getMerchantId(),
                self::PARTNER_MERCHANT_ID => $this->ba->getPartnerMerchantId(),
                self::ACCOUNT_ID          => $this->ba->getAccountId(),
                self::ROUTE               => $this->router->currentRouteName(),
                self::PASSPORT_AUTH       => $this->requestContext->shouldAuthenticateUsingPassport,
                self::STATUS              => $res->getStatusCode(),
                self::AUTH                => $this->ba->getAuthType(),
                self::AUTH_FLOW           => $this->app['request.ctx']->getAuthFlowType(),
                self::ACCOUNT_ID_SOURCE   => $this->app['request.ctx']->getAccountIdSource(),
                "is_mwi_skipped"          => $this->ba->is_mwi_converted_to_merchant_auth,        // temp variable will be removed by edge team
            ]);
        }

        return $res;
    }

    /**
     * set auth type related basic auth contexts from edge passport (auth type, auth flow type, is partner auth)
     */
    private function setAuthTypesBasicAuthContextsFromPassport(string $authType): void
    {
        $this->ba->setBasicType($authType);
        $authFlowType = $this->isPartnerAuth ? self::PARTNER : self::KEY;
        $this->app['request.ctx']->setAuthFlowType($authFlowType);
        $this->ba->setPartnerAuth($this->isPartnerAuth);
    }

    /**
     * set and init authCreds class; and sets mode from edge passport
     */
    private function setAuthCredsBasicAuthContextFromPassport(): void
    {
        $authCredsClass = $this->isPartnerAuth ? ClientAuthCreds::class : KeyAuthCreds::class;
        $this->ba->authCreds = new $authCredsClass($this->app, $this->passport->credential->publicKey);
        $this->ba->authCreds->setModeAndDbConnection($this->passport->mode);
    }

    /**
     * set and init authCreds class; and sets mode from edge passport
     */
    private function setKeylessAuthContextFromPassport(): void
    {
        $authCredsClass = $this->isPartnerAuth ? ClientAuthCreds::class : KeyAuthCreds::class;
        $this->ba->authCreds = new $authCredsClass($this->app);
        $this->ba->authCreds->setModeAndDbConnection($this->passport->mode);

    }

    /**
     * sets account id from edge passport, set source of it to metrics and remove it from request params
     */
    private function setAccountIdFromPassport(): void
    {
        $this->ba->authCreds->creds[self::ACCOUNT_ID] = $this->passportUtil->getAccountId();
        // set source of account id to metrics
        app('request.ctx')->setAccountIdSource();
        // remove account id from request params, does not throw any exception if not present
        // fails payment create validators otherwise
        $this->passportUtil->removeRequestKey(self::ACCOUNT_ID);
    }

     /**
      * set basic auth contexts from edge passport that are common to all auth types
      *
      * @return ApiResponse|null
      * @throws BadRequestException
      */
    private function setCommonBasicAuthContextsFromPassport(string $authType)
    {
        $this->setAuthTypesBasicAuthContextsFromPassport($authType); // set auth type and auth flow type
        $this->setAuthCredsBasicAuthContextFromPassport(); // determine and init authCreds class, also sets mode from passport

        // split by '-' to remove -acc_ if present in public key to get key
        // get last 14 chars to get key id
        $this->ba->authCreds->creds[self::KEY_ID] = substr($this->passport->credential->username, -14);

        // ideally no business logic should need key entity, will be set only for merchant auth
        // TODO: remove setting key entity object
        if ((! $this->isPartnerAuth) and ($this->passport->consumer->type !== BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION)) {
            $error = $this->ba->setKeyEntityFromKeyId(false);
            if ($error !== null) {
                return $error;
            }
        }

        $this->ba->authCreds->setPublicKey($this->passport->credential->publicKey);

        $this->setAccountIdFromPassport();

        $mid = $this->passport->consumer->id;

        $headerMID = $this->app['request']->headers?->get(BasicAuth::RZP_MERCHANT_ID_HEADER);
        if (($this->passport->consumer->type === BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION) and
            (empty($headerMID) === false) and (Entity::verifyUniqueId($headerMID, false) === true))
        {
            $mid = $headerMID;
        }

        $this->ba->setMerchantById($mid);

        // do not check merchant activated status of parent merchant for partner auth
        // since partner's access to live mode doesn't matter while accessing sub merchant resources.
        if (! $this->isPartnerAuth) {
            $error = $this->passportUtil->doMissingChecksAtEdge();
            if ($error !== null) {
                throw $error;
            }
        }
        return null;
    }

    /**
     * set basic auth contexts from edge passport that are common to all auth types
     *
     * @return ApiResponse|null
     * @throws BadRequestException
     */
    private function setCommonBasicAuthContextsFromKeylessPassport(string $authType)
    {
        $this->setAuthTypesBasicAuthContextsFromPassport($authType); // set auth type and auth flow type
        $this->setKeylessAuthContextFromPassport();

        // x_entity_id removal will not be supported on edge. This is a temporary workaround.
        $this->ba->removeRequestKey(self::X_ENTITY_ID_QUERY_KEY);

        $mid = $this->passport->consumer->id;

        $this->ba->setMerchantById($mid);

        // do not check merchant activated status of parent merchant for partner auth
        // since partner's access to live mode doesn't matter while accessing sub merchant resources.
        if (! $this->isPartnerAuth) {
            $error = $this->passportUtil->doMissingChecksAtEdge();
            if ($error !== null) {
                throw $error;
            }
        }

        return null;
    }

    /**
     * for public auth set basic auth contexts from edge passport that are required by business logic
     *
     * @return ApiResponse|null
     * @throws BadRequestException
     */
    private function setPublicBasicAuthContextsFromPassport()
    {
        if ($this->passport->credential === null) {
            return null;
        }

        // Determine which method to call based on the presence of username and publicKey
        $err = $this->passportUtil->isKeylessAuth()
            ? $this->setCommonBasicAuthContextsFromKeylessPassport(Type::PUBLIC_AUTH)
            : $this->setCommonBasicAuthContextsFromPassport(Type::PUBLIC_AUTH);

        if ($err !== null) {
            return $err;
        }

        $this->ba->removeRequestKey(self::KEY_ID);  // Remove 'key_id' from query params
        $this->ba->authCreds->creds[self::SECRET] = null;  // set auth credential secret to null

        return $this->passportUtil->checkAndSetPartnerMerchantScope();
    }

    /**
     * for private auth set basic auth contexts from edge passport that are required by business logic
     *
     * @return ApiResponse|null
     * @throws BadRequestException
     */
    private function setPrivateBasicAuthContextsFromPassport()
    {
        // set basic auth contexts from passport, that are common to all auth types
        $ret = $this->setCommonBasicAuthContextsFromPassport(Type::PRIVATE_AUTH);
        if ($ret !== null)
        {
            return $ret;
        }

        // will not throw any error as account id existence is already verified by edge
        $this->ba->checkAndSetAccountScope();

        return $this->passportUtil->checkAndSetPartnerMerchantScope();
    }


    /**
     * set basic auth contexts from edge passport that are required by business logic
     *
     * @param string $authType
     * @return ApiResponse|null
     * @throws BadRequestException
     */
    private function setBasicAuthContextsFromPassport(string $authType)
    {
        switch ($authType)
        {
            case Type::PUBLIC_AUTH:
                return $this->setPublicBasicAuthContextsFromPassport();
            case Type::PRIVATE_AUTH:
                return $this->setPrivateBasicAuthContextsFromPassport();
        }
    }

    /**
     * set auth type and auth flow type
     */
    private function setAuthTypesOauthContextsFromPassport(string $authType): void
    {
        $this->ba->setBasicType($authType);
        $this->app['request.ctx']->setAuthFlowType(self::OAUTH);
    }

    /**
     * set public key from passport oauth
     */
    private function setPublicKeyOauthContext(string $authType): void
    {
        // Public key is used to generate the callback URL parameter that is
        // being sent with the payment create request to the gateway.
        $publicKey = $this->passport->credential->publicKey;;
        $this->ba->oauthPublicTokenAuth($publicKey, $authType);
    }

    private function setMerchantByIdFromPassportOauth(): string|null
    {
        $merchantId = $this->passport->oauth->ownerId;;
        $this->ba->setMerchantById($merchantId);
        return $merchantId;
    }

    private function setUserByIdFromPassportOauth(string $merchantId): string|null
    {
        $userId = $this->passport->oauth->userId;;
        try
        {
            $this->ba->setUserById($userId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::USER_CONTEXT_NOT_PRESENT_FOR_OAUTH_REQUEST,
                [OAuthToken::MERCHANT_ID => $merchantId, 'error' => $ex]);
        }
        return $userId;
    }

    /**
     * set oauth contexts from edge passport that are common to all auth types
     *
     * @param string $authType
     * @return ApiResponse|null
     */
    private function setCommonOauthContextsFromPassport(string $authType)
    {
        $this->setPublicKeyOauthContext($authType); // set public key and mode from passport

        // authCreds will be initialized by oauthPublicTokenAuth (inside setPublicKeyOauthContext)
        $this->ba->authCreds->setModeAndDbConnection($this->passport->mode);

        $merchantId = $this->setMerchantByIdFromPassportOauth();
        $userId     = $this->setUserByIdFromPassportOauth($merchantId);

        // keeping the merchant activation check in here as this will be supported by Edge in future
        // this can be removed once edge starts supporting it natively
        $error = $this->passportUtil->doMissingChecksAtEdge();
        if ($error !== null) {
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED);
        }

        $this->setAccountIdFromPassport();

        // this flow is required for non pure platform partners who use oauth (Client Credentials mostly).
        $error = $this->passportUtil->handleAccountAuthIfApplicable();
        if ($error !== null)
        {
            return $error;
        }

        $this->ba->setAccessTokenId($this->passport->oauth->accessTokenId);
        $this->ba->setOAuthClientId($this->passport->oauth->clientId);
        $this->ba->setOAuthApplicationId($this->passport->oauth->appId);
        $this->ba->setUserRoleWithUserIdAndMerchantId($merchantId, $userId);
        $this->ba->setTokenScopes($this->passportUtil->fetchOauthScopes());

        $application = (new Repository())->findOrFail($this->passport->oauth->appId);
        $this->ba->setPartnerMerchantId($application->getMerchantId());

        return null;
    }

    /**
     * for public auth set oauth contexts from edge passport that are required by business logic
     *
     * @param string $authType
     * @return ApiResponse|null
     */
    private function setPublicOauthContextsFromPassport()
    {
        $this->setAuthTypesOauthContextsFromPassport(Type::PUBLIC_AUTH); // set auth type and auth flow type

        // If the request was authenticated with key_id sent in the request params
        // we remove the key_id attribute before proceeding
        $this->ba->removeRequestKey(self::KEY_ID);

        // set oauth contexts common to all auth types
        return $this->setCommonOauthContextsFromPassport(Type::PUBLIC_AUTH);
    }

    /**
     * for private auth set oauth contexts from edge passport that are required by business logic
     *
     * @param string $authType
     * @return ApiResponse|null
     */
    private function setPrivateOauthContextsFromPassport()
    {
        $this->setAuthTypesOauthContextsFromPassport(Type::PRIVATE_AUTH); // set auth type and auth flow type

        // set oauth contexts common to all auth types
        return $this->setCommonOauthContextsFromPassport(Type::PRIVATE_AUTH);
    }

    /**
     * set oauth contexts from edge passport that are required by business logic
     *
     * @param string $authType
     * @return ApiResponse|null
     */
    private function setOauthContextsFromPassport(string $authType)
    {
        switch ($authType)
        {
            case Type::PUBLIC_AUTH:
                return $this->setPublicOauthContextsFromPassport();
            case Type::PRIVATE_AUTH:
                return $this->setPrivateOauthContextsFromPassport();
        }
    }

    /**
     * checks if passport can be used with internal auth or not for current request. it also validates internal auth credentials as well.
     *
     * @param string $route
     * @param \Illuminate\Http\Request  $request
     * @return bool
     * @throws BadRequestException
     */
    public function shouldUsePassportWithInternalAuth(string $route,\Illuminate\Http\Request $request) : bool
    {

        //check if route is eligible to be used with passport and internal auth
        if ( !$this->route->isInternalAuthWithPassportRoutes($route) ){
            return false;
        }

        $username = $request->getUser();
        $password = $request->getPassword();

        //request doesn't have a internal auth username(rzp_live or rzp_test)
        //return false.
        if (
            empty($username) ||
            empty($password) ||
            !$this->isInternalAuthUsername($username)
        )
        {
            return false;
        }

        //request doesn't have a edge passport.
        //request doesn't have a usable edge passport.
        if (
            !$this->requestContext->hasPassportJwt ||
            empty($this->passportUtil) ||
            !$this->passportUtil->validatePassport()
        )
        {
            //need to reject the request here after logs confirmation
            $this->trace->info(TraceCode::INTERNAL_AUTH_PASSPORT_CHECKS_FAILED);
            return  false;
        }

        //mode in username, doesn't match mode in passport
        if (!$this->validateModeAccess($username, $this->passport->mode))
        {
            //need to reject the request here after logs confirmation
            $this->trace->info(TraceCode::INTERNAL_AUTH_PASSPORT_MODE_MISMATCH,
                [
                    self::MODE => $this->passport->mode,
                    'username' => $username
                ]
            );
            return  false;
        }

        // validate the app secret is correct and route is added in access list.
        if (!$this->ba->verifyInternalApp($password)) {
            //planning to throw 401 from here not adding right now to avoid failure of any existing incorrect integrations
            $this->trace->info(TraceCode::INTERNAL_AUTH_PASSPORT_APP_VERIFICATION_FAILED);
            return false;
        };

        //blacklisting eztap app to go via this flow because it has a custom integration and can be migrated to new integration as it has only one route.
        // https://razorpay.slack.com/archives/C012ZGQQFDJ/p1694012076645759?thread_ts=1693477006.638119&cid=C012ZGQQFDJ
        if ($this->ba->isEzetapApiApp()) {
            return false;
        }

        return true;
    }

    public function isInternalAuthUsername(string $username):bool
    {
        return $username == 'rzp_live' || $username == 'rzp_test';
    }


    public function validateModeAccess(string $username, string $passportMode):bool
    {
        return substr($username, 4, 4) === $passportMode;
    }

    // getPassportAuthType evaluate the auth type from the passport
    //  will not encounter { authenticate: false, identified: false } since edge will terminate request
    //  TODO: this logic has to be updated once Edge Passport is usable for other auth schemes as well
    public function getPassportAuthType():string
    {
        return ($this->passport->authenticated === false && $this->passport->identified === true) ? Type::PUBLIC_AUTH : Type::PRIVATE_AUTH;
    }
}
