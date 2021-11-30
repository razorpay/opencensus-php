<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;

use ApiResponse;
use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Trace\Tracer;
use RZP\Http\P2pRoute;
use RZP\Http\FeatureAccess;
use RZP\Http\Response\Header;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Edge\PreAuthenticate;
use RZP\Http\Edge\PostAuthenticate;

class Authenticate
{
    // Lists of metrics
    const METRIC_AUTH_HANDLE_MILLISECONDS = 'authenticate_handle_milliseconds.histogram';

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

    protected $router;

    protected $requestContext;

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

        $this->oauth = new OAuth();
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

        (new PreAuthenticate)->handle($request);

        $route = $this->router->currentRouteName();

        $this->ba->init();

        $bearerToken = $this->getBearerTokenFromHeaders($request);

        //
        // If the request was sent with Bearer auth (OAuth),
        // authenticate with the access token, else go for the
        // otherwise existing key-secret flow
        //
        if (empty($bearerToken) === false)
        {
            $ret = $this->authenticateBearerAuth($route, $bearerToken);
        }
        else
        {
            $ret = $this->authenticateBasicAuth($route);
        }

        app()->trace->histogram(
            self::METRIC_AUTH_HANDLE_MILLISECONDS,
            millitime() - $startAt,
            $this->ba->getRequestMetricDimensions());

        // Any not null $ret (e.g. 401, 403 etc) means the request was not authenticated.
        // At the same time a null $ret, in case of direct route still means request was not authenticated(read- not required).
        $authenticated = (($ret === null) and ($this->ba->isDirectAuth() === false) and ($this->ba->isPublicAuth() === false));

        (new PostAuthenticate)->handle($authenticated, $request);

        // Post process after authentication completes
        $ret = (new FeatureAccess)->verifyFeatureAccess($ret, $bearerToken);

        // white listing org and merchants based on features
        if ($ret === null)
        {
            $ret = (new FeatureAccess)->verifyOrgAndMerchantFeatureAccess();
        }

        // null value indicates failure flow : do not validate further if previous validation failed
        if ($ret === null)
        {
            $ret = (new FeatureAccess)->verifyOrgLevelFeatureAccess();
        }

        $passport = $this->requestContext->passport;

        if (($passport !== null) and
            ($passport->consumer !== null) and
            ($passport->consumer->type !== null) and
            ($passport->consumer->id !== null))
        {
            Tracer::addAttribute($this->requestContext->passport->consumer->type, $this->requestContext->passport->consumer->id);
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
            $ret = $this->ba->privateAuth();
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
            $ret = $this->ba->publicAuth();
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
        if (in_array($route, Route::$private, true) === false)
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

        $authHeader = getallheaders()['Authorization'] ?? null;

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
}
