<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;

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

        $ret = $this->authenticateBasicAuth($route);

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    protected function authenticateBasicAuth(string $route)
    {
        $ba = $this->ba;

        $ba->init($this->app);

        $ret = null;

        if (in_array($route, Route::DISABLED_ROUTES, true) === true)
        {
            return ApiResponse::routeDisabled();
        }

        if (in_array($route, Route::$internal, true) === true)
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
        else if (in_array($route, Route::$admin, true) === true)
        {
            $ret = $ba->adminAuth();
        }
        else if (in_array($route, Route::$direct, true) === true)
        {
            ; // $ret = $ba->proxyAuth();
        }
        else
        {
            $ret = ApiResponse::routeNotFound();
        }

        if ($ret !== null)
        {
            return $ret;
        }

        return $ba->feature();

    }
}
