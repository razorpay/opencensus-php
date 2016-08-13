<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthenticateAdmin {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!Auth::guard('admin')->user())
        {
            return response()->json(array('success' => false, 'errors' => ['Unauthorised']), 401);
        }
        return $next($request);
    }
}
