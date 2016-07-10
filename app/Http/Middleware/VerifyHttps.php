<?php

namespace RZP\Http\Middleware;

use RZP\Http\ApiResponse;
use Closure;

class VerifyHttps
{
    const PRODUCTION_HOSTS = [
        'alpha.razorpay.com',
        'beta.razorpay.com',
        'api.razorpay.com'
    ];

    public function handle($request, Closure $next)
    {
        $host = $request->getHttpHost();

        if ((in_array($host, self::PRODUCTION_HOSTS)) and
            ($request->secure() === false))
        {
            return ApiResponse::onlyHttpsAllowed();
        }

        return $next($request);
    }
}
