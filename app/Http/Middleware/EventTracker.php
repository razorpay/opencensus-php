<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

class EventTracker
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
     * @param Application $app
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
     * Data can be sent to lumberjack and eventManager once the
     * response has been already sent.
     *
     * @param $request
     * @param $response
     */
    public function terminate($request, $response)
    {
        $this->sendEventsToLumberjack();

        $this->sendEventsToEventManager();

        $this->sendEventsToSegmentAnalytics();

        $this->sendEventsToXSegment();
    }

    /**
     * Data can be sent to segment-analytics once the response has been already sent.
     */
    protected function sendEventsToSegmentAnalytics()
    {
        try
        {
            $this->app['segment-analytics']->buildRequestAndSend();
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e);
        }
    }

    protected function sendEventsToXSegment()
    {
        try
        {
            $this->app['x-segment']->buildRequestAndSend();
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e);
        }
    }

    /**
     * Data can be sent to lumberjack once the response has been already sent.
     */
    protected function sendEventsToLumberjack()
    {
        try
        {
            $this->app['diag']->buildRequestAndSend();

            $this->app['segment']->buildRequestAndSend();
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e);
        }
    }

    /**
     * Data can be sent to eventManager once the response has been already sent.
     */
    protected function sendEventsToEventManager()
    {
        try
        {
            $this->app['eventManager']->buildRequestAndSend();
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e);
        }
    }
}
