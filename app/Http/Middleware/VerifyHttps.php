<?php

namespace RZP\Http\Middleware;

use RZP\Http\ApiResponse;
use Closure;

class VerifyHttps
{
    public function handle($request, Closure $next)
    {
        if (($request->getHttpHost() === 'api.razorpay.com') and
            ($request->secure() === false))
        {
            return ApiResponse::onlyHttpsAllowed();
        }

        return $next($request);
    }
}
