<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use RZP\Error\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use RZP\Models\Base\UniqueIdEntity;

/**
 * Class EventTrackIDHandler
 *
 *  Handles an incoming request with Header X-Razorpay-TrackId .
 * @package RZP\Http\Middleware
 */

class RequestContextHandler
{
    protected $app;


    public function __construct(Application $app)
    {
        $this->app = $app;

    }

    public function handle(Request $request, Closure $next)
    {

        $trackId = $request->headers->get('X-Razorpay-TrackId');

        if ($trackId !== null)
        {
            $this->app['req.context']->setTrackId($trackId);
        }
        else
        {
            $this->app['req.context']->setTrackId(UniqueIdEntity::generateUniqueId());
        }

        return $next($request);
    }
}
