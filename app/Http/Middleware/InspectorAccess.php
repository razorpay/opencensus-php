<?php

namespace RZP\Http\Middleware;

use Closure;
use Request;
use ApiResponse;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Foundation\Application;

class InspectorAccess
{
    protected $app;
    protected $repo;
    protected $router;

    const DEBUGBAR_ROUTE_PREFIX = '/_debugbar';
    const INSPECT_QUERY_PARAM   = 'inspect';

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];

        $this->router = $app['router'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(HttpRequest $request, Closure $next)
    {
        // If the app is not in debug mode, skip
        if ($this->app->config['app.debug'] !== true)
        {
            return $next($request);
        }

        $requestUri = $request->getRequestUri();

        if (($this->isDebugbarInternalRoute($requestUri) === true) or
            ($request->exists(self::INSPECT_QUERY_PARAM) === true))
        {
            $this->enableDebugbar();

            // Remove the `inspect` query param, if sent
            $request->query->remove(self::INSPECT_QUERY_PARAM);
        }

        return $next($request);
    }

    protected function enableDebugbar()
    {
        \Debugbar::enable();
    }

    protected function isDebugbarInternalRoute(string $uri)
    {
        return (starts_with($uri, self::DEBUGBAR_ROUTE_PREFIX) === true);
    }
}
