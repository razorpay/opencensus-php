<?php

namespace RZP\Http\Middleware;

use Config;
use Closure;
use RZP\Http\Route;

class SessionMinimumLifetime
{
    const routeLifetimeMap = [
        'freshdesk_post_reply'          => 15,
        'freshdesk_fetch_conversations' => 15,
        'freshdesk_fetch_tickets'       => 15,
        'freshdesk_raise_grievance'     => 15,
        'freshdesk_create_ticket'       => 15,
    ];

    public function handle($request, Closure $next)
    {
        $routeName              = $request->route()->getName();

        Config::set('session.lifetime', self::routeLifetimeMap[$routeName]);

        return $next($request);
    }
}