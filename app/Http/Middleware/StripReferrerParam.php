<?php

namespace RZP\Http\Middleware;

use Closure;

class StripReferrerParam
{
    public function handle($request, Closure $next)
    {
        $requestBody = $request->all();

        if ((isset($requestBody['_']) === true) and
            (isset($requestBody['_']['referer']) === true))
        {
            $requestBody['_']['referer'] = preg_replace('/\\?.*/', '', $requestBody['_']['referer']);

            $request->replace($requestBody);
        }

        return $next($request);
    }
}
