<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Illuminate\Foundation\Application;
use RZP\Http\Route;

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
     * @param  Guard  $auth
     * @return void
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

        if (in_array($route, Route::$internal))
        {
            $ret = $ba->appAuth();
        }
        else if (in_array($route, Route::$private))
        {
            $ret = $ba->privateAuth();
        }
        else if (in_array($route, Route::$public))
        {
            $ret = $ba->publicAuth();
        }
        else if (in_array($route, Route::$publicCallback))
        {
            $ret = $ba->publicCallbackAuth();
        }
        else if (in_array($route, Route::$proxy))
        {
            $ret = $ba->proxyAuth();
        }
        else if (in_array($route, Route::$direct))
        {
            ; // $ret = $ba->proxyAuth();
        }
        else if (in_array($route, Route::$admin))
        {
            $ret = $ba->adminAuth();
        }
        else
        {
            return ApiResponse::routeNotFound();
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
}
