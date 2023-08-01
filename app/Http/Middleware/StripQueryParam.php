<?php


namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StripQueryParam
{
    /**
     * Handle incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed|void
     */
    public function handle($request, Closure $next)
    {
        $input = $request->query->all();

        if (array_key_exists('temporary_token', $input) === true) {

            unset($input['temporary_token']);

            $request->query->replace($input);
        }

        if (array_key_exists('keyless_header', $input) === true) {

            unset($input['keyless_header']);

            $request->query->replace($input);
        }

        $request->query->remove('x_customer_access_token');

        return $next($request);
    }
}
