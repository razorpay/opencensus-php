<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Response;

class GraphAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ((empty($_SERVER['PHP_AUTH_USER']) or empty($_SERVER['PHP_AUTH_PW'])) or
            (($_SERVER['PHP_AUTH_USER'] !== config('graph.auth_user')) or
            ($_SERVER['PHP_AUTH_PW'] !== config('graph.auth_pass'))))
        {
            return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
        }

        return $next($request);
    }
}
