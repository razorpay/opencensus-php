<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

class Segment
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
        return $next($request);
    }

    /**
     * Data can be sent to segment once the response has been
     * already sent.
     * See doc for terminable middleware -
     *     https://laravel.com/docs/5.2/middleware#terminable-middleware
     */
    public function terminate($request, $response)
    {
        //
        // send the in-memory segment events to lumberjack
        //

        try
        {
            $this->app['segment']->buildRequestAndSend();
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e);
        }
        catch (\Error $e)
        {
            $this->app['trace']->traceError($e);
        }
    }
}
