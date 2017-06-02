<?php namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Admin;

class Verified
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('user')->user();

        if ($user and $user->confirmed)
        {
            return $next($request);
        }
        else
        {
            return response('Unauthorized.', 401);
        }
    }
}
