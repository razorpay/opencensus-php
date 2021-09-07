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
        // raise the event only if the request is successful
        if ($response->getStatusCode() < 300)
        {
            event(new TriggerSyncEvent());
        }
        else
        {
            $this->app[SyncEventManager::SINGLETON_NAME]->resetAccountParams();
        }
    }
}
