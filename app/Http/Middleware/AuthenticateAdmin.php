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

        $adminUsername = $admin->username ?? null;
        ApiRequest::addHeader('X-Dashboard-Admin-Username', $adminUsername);

        $adminEmail = $admin->email ?? null;
        ApiRequest::addHeader('X-Dashboard-Admin-Email', $adminEmail);

        return $next($request);
    }
}
