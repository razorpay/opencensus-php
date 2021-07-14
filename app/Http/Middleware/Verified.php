<?php namespace App\Http\Middleware;

use Closure;
use Auth;
use Route;
use Request;
use App\Admin;

class Verified
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('user')->user();

        $xSignUpFlowV2 = Request::header('x-signup-flow-v2');

        $routeName = Route::currentRouteName();

        if ($user and
           (($user->confirmed) ||
            ($routeName === 'merchant' && $xSignUpFlowV2 === 'true')))
        {
            return $next($request);
        }
        else
        {
            return response('Unauthorized.', 401);
        }
    }
}
