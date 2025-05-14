<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Response;

class InternalAuth {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check against API credentials
        $isApiAuth = ($_SERVER['PHP_AUTH_USER'] === config('api.auth_user')) &&
                    ($_SERVER['PHP_AUTH_PW'] === config('api.auth_pass'));
        
        // Check against IDP credentials
        $isIdpAuth = ($_SERVER['PHP_AUTH_USER'] === config('idp.auth_user')) &&
                    ($_SERVER['PHP_AUTH_PW'] === config('idp.auth_pass'));
        
        // If neither auth is valid, return unauthorized
        if (!$isApiAuth && !$isIdpAuth)
        {
            return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
        }

        return $next($request);
    }
}
