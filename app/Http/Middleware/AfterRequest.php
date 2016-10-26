<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

class AfterRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $app;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // send the in-memory segment events to lumberjack
        try
        {
            $this->app['segment']->buildRequestAndSend();
        }
        catch(\Exception $e)
        {}


        return $response;
    }
}
