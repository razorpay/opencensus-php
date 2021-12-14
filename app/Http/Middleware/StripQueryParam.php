<?php


namespace RZP\Http\Middleware;

use Closure;

class StripQueryParam
{
    public function handle($request, Closure $next)
    {
        $input = $request->query->all();

        if (array_key_exists('temporary_token', $input) === true) {

            unset($input['temporary_token']);

            $request->query->replace($input);
        }

        return $next($request);
    }
}
