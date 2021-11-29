<?php


namespace RZP\Http\Middleware;

use Closure;

class StripQueryParam
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

        $input = $request->query->all();

        if (array_key_exists('temporary_token', $input) === true) {

            unset($input['temporary_token']);

            $request->query->replace($input);
        }

        return $next($request);
    }
}
