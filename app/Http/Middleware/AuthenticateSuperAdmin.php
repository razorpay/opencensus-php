<?php namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthenticateSuperAdmin {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!Auth::guard('admin')->user()->isSuperAdmin())
        {
            return response()->json(array('success' => false, 'errors' => ['Unauthorised']));
        }
        return $next($request);
    }
}
