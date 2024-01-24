<?php namespace App\Http\Middleware;

use App\Metrics\Constants;
use Closure;
use Auth;
use Route;
use Request;
use App\Admin;
use App\Trace\TraceCode;
use Trace;
use App\Http\ApiUrl;

class Verified
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('user')->user();

        $xSignUpFlowV2 = Request::header('x-signup-flow-v2');

        $routeName = Route::currentRouteName();

        $path = $request->path();

        if (isset($user) === true && ($routeName === 'merchant' || $routeName === 'oauth_merchant') && !$user->confirmed && !$user->contact_mobile_verified && $xSignUpFlowV2 === 'true' ){
            Trace::info(TraceCode::INFO_USES_X_SIGNUP_FLOW_V2_HEADER, [
                'route_name'     => $routeName,
                'path' => $path,
                'method' => $request->getMethod() ?? 'unknown_method',
                'origin' => ApiUrl::getRequestOrigin(),
                'domain' => $request->server->get('SERVER_NAME') ?? 'unknown_domain',
                'graphql_client' => $request->header('apollographql-client-name'),
                'product' => ApiUrl::isBankingOriginRequest() ? Constants::BANKING : Constants::PRIMARY ,
            ]);
        }


        if (isset($user) === true)
        {
            // one of these conditions should be met:
            // i. user has verified their email
            // ii. user has verified their phone number
            // iii. it's X signup flow on `merchant` route
            $isUserVerified = (
                ($user->confirmed) ||
                ($user->contact_mobile_verified) ||
                (($routeName === 'merchant' ||
                  $routeName === 'oauth_merchant') &&
                  $xSignUpFlowV2 === 'true')
            );

            if ($isUserVerified === true)
            {
                return $next($request);
            }
        }

        return response('Unauthorized.', 401);
    }
}
