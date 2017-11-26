<?php

namespace RZP\Http\Middleware;

use ApiResponse;

use Closure;
use RZP\Http;
use Illuminate\Routing\Router;
use Illuminate\Foundation\Application;

class Route
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->router = $app['router'];
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

        // Check for disabled routes
        if (in_array($route, Http\Route::DISABLED_ROUTES, true) === true)
        {
            return ApiResponse::routeDisabled();
        }

        return $next($request);
    }
}
