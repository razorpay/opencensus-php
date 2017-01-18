<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Razorpay\Api\Request as ApiRequest;

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
        $admin = Auth::guard('api')->user();

        if (!$admin)
        {
            return response()->json(array('success' => false, 'errors' => ['Unauthorised']), 401);
        }

        $adminUsername = $admin->username;
        ApiRequest::addHeader('X-Dashboard-Username', $adminUsername);

        return $next($request);
    }
}
