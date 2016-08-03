<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Razorpay\Api\Request as ApiRequest;

class AddDashboardHeaders
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
        $user = Auth::guard('user')->user();

        if ($user)
        {
            ApiRequest::addHeader('X-Dashboard-Merchant', $user->email);
        }

        // Just in case an admin user is performing the action
        $admin = Auth::guard('admin')->user();

        if ($admin)
        {
            $adminUsername = $admin->username;
            ApiRequest::addHeader('X-Dashboard-Username', $adminUsername);
        }

        return $next($request);
    }
}




