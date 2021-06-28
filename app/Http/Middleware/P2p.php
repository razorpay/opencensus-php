<?php

namespace RZP\Http\Middleware;

use Closure;
use RZP\Trace\TraceCode;
use Illuminate\Foundation\Application;
use RZP\Models\P2p\Base\Libraries\Context;

class P2p
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->context = $this->app['p2p.ctx'];
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
        $this->context->loadWithRequest($request);

        $response = $next($request);

        $executionTime = microtime(true) - LARAVEL_START;

        $this->app['trace']->info(TraceCode::P2P_RESPONSE, [
            'type'                  => 'response_time',
            'time_taken_in_seconds' => $executionTime,
        ]);

        $response->header('X-Razorpay-Request-Id', $this->context->getRequestId(), true);

        return $response;
    }
}
