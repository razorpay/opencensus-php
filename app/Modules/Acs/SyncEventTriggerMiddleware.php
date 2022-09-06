<?php

namespace RZP\Modules\Acs;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Class SyncEventTriggerMiddleware
 *
 * Handles triggering sync events to account service via outboxer on request termination
 *
 * @package RZP\Http\Middleware
 */
class SyncEventTriggerMiddleware
{
    /**
     * @param Application $app
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        /*
           This raises the event for all requests, irrespective of whether the request fails or succeeds
           this is to ensure data is always synced between API<>ASV even in 4xx/5xx requests
           which may create or update one or more entities in API.
        */
        event(new TriggerSyncEvent());
    }
}
