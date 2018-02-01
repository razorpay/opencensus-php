<?php

namespace RZP\Http\Middleware;

use ApiResponse;
use RZP\Http\Throttle\Throttle;

final class ThrottleV2
{
    public function handle($request, \Closure $next)
    {
        (new Throttle)->throttle($request);

        return $next($request);
    }
}
