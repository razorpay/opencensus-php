<?php

namespace RZP\Http\Middleware;

use ApiResponse;
use RZP\Http\Throttle\Throttle;

final class ThrottleV2
{
    public function handle($request, \Closure $next)
    {
        $limits   = (new Throttle)->throttle($request);
        $response = $next($request);

        return ApiResponse::withRateLimitHeaders($response, $limits);
    }
}
