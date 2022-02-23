<?php

namespace RZP\Http\Middleware;

use Closure;
use RZP\Http\BasicAuth\BasicAuth;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request as HttpRequest;

class AddDashboardResponseHeaders
{
    protected $app;

    /** @var BasicAuth */
    protected $ba;

    const API_ROUTE_NAME   = 'api-route-name';

    const API_PATH_PATTERN = 'api-path-pattern';

    // for below internal apps dashboard headers will be added
    const DASHBOARD_APPS    = ['admin_dashboard', 'merchant_dashboard', 'dashboard_guest', 'dashboard_internal'];

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];
    }

    /**
     * Handle an incoming request.
     *
     * @param HttpRequest $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(HttpRequest $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldAddHeadersForApp($this->ba->getInternalApp()) === true)
        {
            $routeName = $this->app['router']->currentRouteName();

            $routeInfo = $this->app['api.route']->getApiRoute($routeName);

            $response->headers->set(self::API_ROUTE_NAME, $routeName);

            $response->headers->set(self::API_PATH_PATTERN, $routeInfo[1]);
        }

        return $response;
    }

    protected function shouldAddHeadersForApp($internalAppName) : bool
    {
        return (in_array($internalAppName, self::DASHBOARD_APPS, true) === true);
    }
}
