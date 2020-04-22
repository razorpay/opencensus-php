<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Http\Request as HttpRequest;

class ProxySQL
{
    public function handle(HttpRequest $request, Closure $next)
    {
        // Request content is initialised here and in Throttle middleware also.
        // If you are removing this, then make sure request context is initialised somewhere.
        app('request.ctx')->init();

        app('proxysql.config')->setDatabaseHostsIfApplicable();

        return $next($request);
    }
}
