<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Response;

class OAuth {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($_SERVER['PHP_AUTH_USER'] !== config('oauth.auth_user')) or
        ($_SERVER['PHP_AUTH_PW'] !== config('oauth.auth_pass')))
        {
            return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
        }

        return $next($request);
    }
}
