<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Http\Request as HttpRequest;

class ProxySQL
{
    public function handle(HttpRequest $request, Closure $next)
    {
        app('proxysql.config')->setDatabaseHostsIfApplicable();

        return $next($request);
    }
}
