<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;

use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;
use RZP\Http\OAuth;
use RZP\Http\Scopes;
use RZP\Http\Throttle;
use RZP\Http\BasicAuth\Type;

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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $router = $this->app['router'];

        $route = $router->currentRouteName();

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
            $ret = $ba->publicAuth();
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
     * @param string $route
     * @param string $bearerToken
     *
     * @return mixed|null ErrorResponse if error, else null
     */
    protected function authenticateBearerAuth(string $route, string $bearerToken)
    {
        //
        // Only `private` auth endpoints may be accessed
        // on OAuth
        //
        if (in_array($route, Route::$private, true) === false)
        {
            return ApiResponse::routeNotFound();
        }

        $oauth = new OAuth;

        list($merchantId, $tokenId, $clientId) = $oauth->resolveToken($bearerToken);

        if ($merchantId === null)
        {
            // todo: Change to unauthorized response
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $routeScopes = Scopes::getScopesForRoute($route);

        $scopeAllowed = $this->checkScopes($routeScopes);

        if ($scopeAllowed === false)
        {
            // todo: Change to unauthorized response
            return ApiResponse::httpMethodNotAllowed();
        }

        //
        // Set merchant for the current request
        // TODO: Move this to a common auth class
        //
        $this->ba->setMerchantById($merchantId);

        $this->ba->setMode('test');

        \Database\DefaultConnection::set('test');

        $this->ba->setAccessTokenId($tokenId);

        $this->ba->setOAuthClientId($clientId);
    }

    protected function checkScopes(array $routeScopes) : bool
    {
        foreach ($routeScopes as $scope)
        {
            if ($this->ba->hasScope($scope) === true)
            {
                return true;
            }
        }

        return false;
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

        $this->addTraceDataForMerchantAndAdmin($this->ba);

        if ($featureCheck !== null)
        {
            return $featureCheck;
        }
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
    private function addTraceDataForMerchantAndAdmin($ba)
    {
        $merchantId = $ba->getMerchantIdOfKey();
        $data = ['merchant_id' => $merchantId];

        if ($ba->isDashboardApp())
        {
            $dashboardHeaders = $ba->getDashboardHeaders();

            $data = array_merge($data, $dashboardHeaders);
        }

        $this->app['trace']->processor('web')->addServerData($data);
    }
}
