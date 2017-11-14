<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

use ApiResponse;
use RZP\Http\Route;
use RZP\Http\OAuth;
use RZP\Http\Throttle;
use RZP\Http\BasicAuth\Type;
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
        $router = $this->app['router'];

        $route = $router->currentRouteName();

        // Check for disabled routes
        if (in_array($route, Route::DISABLED_ROUTES, true) === true)
        {
            return ApiResponse::routeDisabled();
        }

        $this->ba->init($this->app);

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
     *
     * @return mixed
     */
    protected function authenticateBasicAuth(string $route)
    {
        $ret = null;

        if ((in_array($route, Route::$internal, true) === true) or
            (in_array($route, Route::$admin, true) === true))
        {
            $this->throttleRequests($route, Type::ADMIN_AUTH);

            $ret = $this->ba->appAuth();
        }
        else if (in_array($route, Route::$private, true) === true)
        {
            $this->throttleRequests($route, Type::PRIVATE_AUTH);

            $ret = $this->ba->privateAuth();
        }
        else if (in_array($route, Route::$public, true) === true)
        {
            $this->throttleRequests($route, Type::PUBLIC_AUTH);

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
        else if (in_array($route, Route::$publicCallback, true) === true)
        {
            $this->throttleRequests($route, Type::PUBLIC_AUTH);

            $ret = $this->ba->publicCallbackAuth();
        }
        else if (in_array($route, Route::$proxy, true) === true)
        {
            $this->throttleRequests($route, Type::PROXY_AUTH);

            $ret = $this->ba->proxyAuth();
        }
        else if (in_array($route, Route::$device, true) === true)
        {
            $this->throttleRequests($route, Type::DEVICE_AUTH);

            $ret = $this->ba->deviceAuth();
        }
        else if (in_array($route, Route::$direct, true) === true)
        {
            $this->throttleRequests($route, Type::DIRECT_AUTH);

            // $ret = $this->ba->proxyAuth();
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

        if ($featureCheck !== null)
        {
            return $featureCheck;
        }

        return null;
    }

    /**
     * This is our global rate throttling mechanism
     *
     * @param string $auth
     */
    private function throttleRequests(string $route, string $auth)
    {
        $throttle = new Throttle($this->app);

        $throttle->process($auth);
    }

    private function getBearerTokenFromHeaders($request)
    {
        //
        // After a fix in infra code we will use following to get bearer token:
        // $request->getBearerToken();
        //
        // But for now following are the issues:
        // - With apache, 'Authorization' headers is missing unless specific
        //   configuration. So for that we started using getallheaders(). This
        //   method is available when PHP is running with apache and so we have
        //   added a polyfill utility method in case on local someone is using
        //   nginx.
        // - Now with tests, it's not actually an HTTP request during request
        //   response flow. Framework forms a Symfony request object and directly
        //   starts from framework kernel's instantiation(by passing actual HTTP flow).
        //   And so extra $_SERVER headers are missing. So in tests using $request's
        //   bearerToken() method.
        //

        if ($this->app->runningUnitTests() === true)
        {
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
}
