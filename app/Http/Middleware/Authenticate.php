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
use Symfony\Component\HttpFoundation\Response;

use ApiResponse;
use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Trace\Tracer;
use RZP\Http\P2pRoute;
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
    const PASSPORT_AUTH = 'passport_auth';

    const PASSPORT_AUTH_TYPE   = 'passport_auth_type';
    const APP_MERCHANT_ID      = 'app_merchant_id';
    const PASSPORT_CONSUMER_ID = 'passport_consumer_id';


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
        if ($this->requestContext->shouldAuthenticateUsingPassport)
        {
            $this->isPartnerAuth = ($this->passport->consumer->type == self::PARTNER);
            $passportAuthType = ($this->passport->authenticated === false && $this->passport->identified === true) ? Type::PUBLIC_AUTH : Type::PRIVATE_AUTH;

            $this->trace->info(TraceCode::AUTHENTICATING_USING_PASSPORT,
                [
                    self::KEY_ID             => $this->passport->credential->publicKey,
                    self::MERCHANT_ID        => $this->passport->consumer->id,
                    self::ROUTE              => $route,
                    self::PASSPORT_AUTH_TYPE => $passportAuthType
                ]
            );

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
                self::HOST               => $request->getHttpHost()
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

        if ((in_array($route, Route::$internal, true) === true) or
            (in_array($route, Route::$admin, true) === true))
        {
            $ret = $this->ba->appAuth();
        }
        else if (in_array($route, Route::$private, true) === true)
        {
            $ret = Tracer::inspan(['name' => HyperTrace::AUTHENTICATE_PRIVATE_ROUTE_PRIVATE_AUTH], function () {
                    return $this->ba->privateAuth();
                });
        }
        else if (in_array($route, P2pRoute::$private, true) === true)
        {
            $ret = $this->ba->privateAuth();
        }
        else if (in_array($route, Route::$public, true) === true)
        {
            //
            // For public routes, OAuth sends a public_token using BasicAuth
            // We check here if the key is an OAuth public token and
            // process accordingly.
            //
            if ($this->oauth->hasOAuthPublicToken() === true)
            {
                $ret = $this->authenticateOAuthPublicToken();
            }
            else
            {
                // Process via BasicAuth
                $ret = $this->ba->publicAuth();
            }
        }
        else if (in_array($route, P2pRoute::$public, true) === true)
        {
            $ret = $this->ba->p2pPublicAuth();
        }
        else if (in_array($route, Route::$publicCallback, true) === true)
        {
            if ($this->ba->hasPartnerAuthCallbackKey() === true)
            {
                $ret = $this->ba->handlePartnerAuthOnPublicCallback();
            }
            else if ($this->oauth->hasOAuthPublicToken() === true)
            {
                $ret = $this->authenticateOAuthPublicToken();
            }
            else
            {
                $ret = $this->ba->publicCallbackAuth();
            }
        }
        else if (in_array($route, Route::$proxy, true) === true)
        {
            $ret = $this->ba->proxyAuth();
        }
        else if (in_array($route, Route::$device, true) === true)
        {
            $ret = $this->ba->deviceAuth();
        }
        else if (in_array($route, P2pRoute::$device, true) === true)
        {
            $ret = $this->ba->p2pDeviceAuth();
        }
        else if (in_array($route, Route::$direct, true) === true)
        {
            $ret = $this->ba->directAuth();
        }
        else if (in_array($route, P2pRoute::$direct, true) === true)
        {
            $ret = $this->ba->directAuth();
        }
        else
        {
            $ret = ApiResponse::routeNotFound();
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

        return $res;
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
        $this->ba->setBasicType($authType);
        $authFlowType = $this->isPartnerAuth ? self::PARTNER : self::KEY;
        $this->app['request.ctx']->setAuthFlowType($authFlowType);
        $this->ba->setPartnerAuth($this->isPartnerAuth);

        $authCredsClass = $this->isPartnerAuth ? ClientAuthCreds::class : KeyAuthCreds::class;
        $this->ba->authCreds = new $authCredsClass($this->app, $this->passport->credential->publicKey);
        $this->ba->authCreds->setModeAndDbConnection($this->passport->mode);

        // split by '-' to remove -acc_ if present in public key to get key
        // get last 14 chars to get key id
        $this->ba->authCreds->creds[self::KEY_ID] = substr($this->passport->credential->username, -14);

        // ideally no business logic should need key entity
        // TODO: remove setting key entity object
        $this->ba->setKeyEntityFromKeyId(false);

        $this->ba->authCreds->setPublicKey($this->passport->credential->publicKey);
        $this->ba->authCreds->creds[self::ACCOUNT_ID] = $this->passportUtil->getAccountId();

        $this->ba->setMerchantById($this->passport->consumer->id);

        $error = $this->passportUtil->doMissingChecksAtEdge();
        if ($error !== null) {
            throw $error;
        }

        // will not throw any error as account id existence is already verified by edge
        $this->ba->checkAndSetAccountScope();

        return $this->passportUtil->checkAndSetPartnerMerchantScope();
    }

    /**
     * set oauth contexts from edge passport that are required by business logic
     *
     * @param string $authType
     * @return ApiResponse|null
     */
    private function setOauthContextsFromPassport(string $authType)
    {
        $this->ba->setBasicType($authType);
        $this->app['request.ctx']->setAuthFlowType(self::OAUTH);

        // Public key is used to generate the callback URL parameter that is
        // being sent with the payment create request to the gateway.
        $publicKey = $this->passport->credential->publicKey;;
        $this->ba->oauthPublicTokenAuth($publicKey, Type::PRIVATE_AUTH);

        // authCreds will be initialized by oauthPublicTokenAuth
        $this->ba->authCreds->setModeAndDbConnection($this->passport->mode);

        $merchantId = $this->passport->oauth->ownerId;;
        $this->ba->setMerchantById($merchantId);

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

        // keeping the merchant activation check in here as this will be supported by Edge in future
        // this can be removed once edge starts supporting it natively
        $error = $this->passportUtil->doMissingChecksAtEdge();
        if ($error !== null) {
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED);
        }

        $this->ba->authCreds->creds[self::ACCOUNT_ID] = $this->passportUtil->getAccountId();

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

        $tokenScopes = $this->passportUtil->fetchOauthScopes();
        $this->ba->setTokenScopes($tokenScopes);

        // TODO: remove this db op if no requests need this and trace log
        $application = (new Repository())->findOrFail($this->passport->oauth->appId);
        $this->ba->setPartnerMerchantId($application->getMerchantId());
        if ($this->passport->consumer->id !== $application->getMerchantId()) {
            $this->trace->info(TraceCode::OAUTH_PARTNER_MERCHANT_MISMATCH,
                [
                    self::APP_MERCHANT_ID      => $application->getMerchantId(),
                    self::PASSPORT_CONSUMER_ID => $this->passport->consumer->id,
                    self::ROUTE                => $this->router->currentRouteName()
                ]
            );
        }

        return null;
    }
}
