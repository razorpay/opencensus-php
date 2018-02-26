<?php

namespace RZP\Http\Middleware;

use ApiResponse;
use RZP\Http\Throttle\Throttler;

final class ThrottleV2
{
    public function handle($request, \Closure $next)
    {
        (new Throttler)->throttle($request);

        return $next($request);
    }
}
