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

        if (isset($user) === true)
        {
            // one of these conditions should be met:
            // i. user has verified their email
            // ii. user has verified their phone number
            // iii. it's X signup flow on `merchant` route
            $isUserVerified = (
                ($user->confirmed) ||
                ($user->contact_mobile_verified) ||
                ($routeName === 'merchant' && $xSignUpFlowV2 === 'true')
            );

            if ($isUserVerified === true)
            {
                return $next($request);
            }
        }

        return response('Unauthorized.', 401);
    }
}
