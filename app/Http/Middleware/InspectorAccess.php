<?php

namespace RZP\Http\Middleware;

use App;
use Closure;
use Debugbar;
use Illuminate\Foundation\Application;
use Illuminate\Cache\Repository as Config;
use Illuminate\Http\Request as HttpRequest;

class InspectorAccess
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Query param used to initiate inspector
     */
    const INSPECT_QUERY_PARAM   = '_inspect';

    public function __construct(Application $app)
    {
        $this->config = $app['config'];
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
        // Skip if the app is not in debug mode, or dev environment
        if (($this->config['app.debug'] !== true) or
            (App::environment() !== 'dev'))
        {
            return $next($request);
        }

        if (($this->isDebugbarInternalRoute($request) === true) or
            ($request->exists(self::INSPECT_QUERY_PARAM) === true))
        {
            Debugbar::enable();

            // Remove the `inspect` query param, if sent
            $request->query->remove(self::INSPECT_QUERY_PARAM);
        }

        return $next($request);
    }

    /**
     * Debugbar defines a few internal routes for AJAX requests,
     * which require Debugbar enabled to work.
     *
     * @see \Barryvdh\Debugbar\ServiceProvider::boot()
     *
     * @param HttpRequest $request
     *
     * @return bool
     */
    protected function isDebugbarInternalRoute(HttpRequest $request) : bool
    {
        $debugBarRoutePrefix = '/' . $this->config->get('debugbar.route_prefix');

        $requestUri = $request->getRequestUri();

        return (starts_with($requestUri, $debugBarRoutePrefix) === true);
    }
}
