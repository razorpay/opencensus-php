<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Middleware\StartSession as BaseMiddleware;

/**
 * Added this wrapper class for graphql server
 * StartSession loads Session class onto Request object,
 * and adds cookies in response
 *
 * By overriding the handle method preventing addCookiesToResponse
 * from being called
 *
 * Cookies will forwarded by downstream Graphql service
 */
class StartSession extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $session = $this->startSession($request);

        $request->setSession($session);

        $this->collectGarbage($session);

        return $next($request);
    }
}