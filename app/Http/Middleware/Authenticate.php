<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use RZP\Http\Route;
use RZP\Http\Throttle;
use RZP\Http\BasicAuth\Type;
use Illuminate\Foundation\Application;

class Authenticate
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $app;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
        $ba = $this->app['basicauth'];

        $ba->init($this->app);

        $ret = null;

        if (in_array($route, Route::DISABLED_ROUTES, true))
        {
            return ApiResponse::routeDisabled();
        }

        if ((in_array($route, Route::$internal, true)) or
            (in_array($route, Route::$admin, true)))
        {
            $ret = $ba->appAuth();
        }
        else if (in_array($route, Route::$private, true))
        {
            $this->throttleRequests(Type::PRIVATE_AUTH);

            $ret = $ba->privateAuth();
        }
        else if (in_array($route, Route::$public, true))
        {
            $this->throttleRequests(Type::PUBLIC_AUTH);

            $ret = $ba->publicAuth();
        }
        else if (in_array($route, Route::$publicCallback, true))
        {
            $this->throttleRequests(Type::PUBLIC_AUTH);

            $ret = $ba->publicCallbackAuth();
        }
        else if (in_array($route, Route::$proxy, true))
        {
            $this->throttleRequests(Type::PROXY_AUTH);

            $ret = $ba->proxyAuth();
        }
        else if (in_array($route, Route::$device, true))
        {
            $this->throttleRequests(Type::DEVICE_AUTH);

            $ret = $ba->deviceAuth();
        }
        else if (in_array($route, Route::$direct, true))
        {
            $this->throttleRequests(Type::DIRECT_AUTH);

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

        $ret = $ba->feature();

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    /**
     * This is our global rate throttling mechanism
     */
    private function throttleRequests(string $auth)
    {
        $throttle = new Throttle($this->app);

        $throttle->process($auth);
    }
}
