<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Response;

class CronAuth {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($_SERVER['PHP_AUTH_USER'] !== \Config::get('cron.auth_user')) or
        ($_SERVER['PHP_AUTH_PW'] !== \Config::get('cron.auth_pass')))
        {
            return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
        }

        return $next($request);
    }
}
