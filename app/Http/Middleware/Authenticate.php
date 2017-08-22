<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;

use RZP\Http\Route;
use RZP\Http\OAuth;
use RZP\Http\Scopes;
use RZP\Http\Throttle;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;

class Authenticate
{
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

    protected $request;

    /**
     * @var OAuth
     */
    protected $oauth;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba  = $this->app['basicauth'];
    }

    /**
     * Handle an incoming request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $router = $this->app['router'];

        $route = $router->currentRouteName();

        $this->request = $request;

        $this->oauth = new OAuth($request);

        // Check for disabled routes
        if (in_array($route, Route::DISABLED_ROUTES, true) === true)
        {
            return ApiResponse::routeDisabled();
        }

        $this->ba->init($this->app);

        $bearerToken = $request->bearerToken();

        //
        // If the request was sent with Bearer auth (OAuth),
        // authenticate with the access token, else go for the
        // old key-secret flow
        //
        if (empty($bearerToken) === false)
        {
            $ret = $this->authenticateBearerAuth($route, $bearerToken);
        }
        else
        {
            $ret = $this->authenticateBasicAuth($route);
        }

        // Post process after auth completes
        $ret = $this->postAuthenticationProcessing($ret);

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    /**
     * Authenticate the request with Basic auth
     *
     * @param string $route
     * @return mixed
     */
    protected function authenticateBasicAuth(string $route)
    {
        $ba = $this->ba;

        $ret = null;

        if ((in_array($route, Route::$internal, true) === true) or
            (in_array($route, Route::$admin, true) === true))
        {
            $ret = $ba->appAuth();
        }
        else if (in_array($route, Route::$private, true) === true)
        {
            $ret = $ba->privateAuth();
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
                $ret = $ba->publicAuth();
            }
        }
        else if (in_array($route, Route::$publicCallback, true) === true)
        {
            $ret = $ba->publicCallbackAuth();
        }
        else if (in_array($route, Route::$proxy, true) === true)
        {
            $ret = $ba->proxyAuth();
        }
        else if (in_array($route, Route::$device, true) === true)
        {
            $ret = $ba->deviceAuth();
        }
        else if (in_array($route, Route::$direct, true) === true)
        {
            ; // $ret = $ba->proxyAuth();
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
     */
    protected function authenticateOAuthPublicToken()
    {
        return $this->oauth->resolvePublicToken();
    }

    /**
     * Post process after auth completes
     * Function returns non-null value for failure flow
     *
     * @param $authReturn
     *
     * @return mixed
     */
    protected function postAuthenticationProcessing($authReturn)
    {
        if ($authReturn !== null)
        {
            return $authReturn;
        }

        $featureCheck = $this->ba->feature();

        $this->addTraceDataForMerchantAndAdmin();

        if ($featureCheck !== null)
        {
            return $featureCheck;
        }

        return null;
    }

    /**
     * This is our global rate throttling mechanism
     */
    private function throttleRequests(string $auth)
    {
        $throttle = new Throttle($this->app);

        $throttle->process($auth);
    }

    /**
     * Adds details in trace for merchant_id who or on whose behalf request
     * is being made. Adds dashboard headers details for admin etc. making
     * the request.
     */
    private function addTraceDataForMerchantAndAdmin()
    {
        $ba = $this->ba;

        $merchantId = $ba->getMerchantIdOfKey();
        $data = ['merchant_id' => $merchantId];

        if ($ba->isDashboardApp() === true)
        {
            $dashboardHeaders = $ba->getDashboardHeaders();

            $data = array_merge($data, $dashboardHeaders);
        }

        $this->app['trace']->processor('web')->addServerData($data);
    }
}
