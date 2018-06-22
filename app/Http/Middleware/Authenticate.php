<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

use ApiResponse;
use RZP\Http\Route;
use RZP\Http\OAuth;
use RZP\Http\FeatureAccess;
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

    protected $router;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $this->app['basicauth'];

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

        // Post process after authentication completes
        $ret = (new FeatureAccess)->verifyFeatureAccess($ret, $bearerToken);

        // Non-null value indicates failure flow
        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
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
        else if (in_array($route, Route::$publicCallback, true) === true)
        {
            if ($this->ba->hasPartnerTokenCallbackKey() === true)
            {
                $ret = $this->ba->handlePartnerTokenOnPublicCallback();
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
        else if (in_array($route, Route::$direct, true) === true)
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
}
