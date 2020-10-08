<?php

namespace App\Http\Middleware;

use Auth;
use Session;
use Closure;
use App\Http\AppResponse;
use App\User\Constants as UserConstants;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Crypt;


class SessionInActivity
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;

        $app = \App::getFacadeRoot();

        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Meta data is stored in session with the key _sf2_meta with keys c,u,l as keys (created, updated, lifetime)
        $metaDataBag = Session::getMetadataBag();

        $user = Auth::guard('user');

        $lastUsed = $metaDataBag->getLastUsed();

        $sessionConfig = $this->app['config']['session'];

        $inActivityTime = $sessionConfig['inactivity_time'] * 60;

        $mobileApp = $request->header('X-Razorpay-App');

        if (empty($mobileApp) === false)
        {
            $inActivityTime = $sessionConfig['mobileapp_inactivity_time'] * 60;
        }

        $currentTime = time();

        $routeName = $request->route()->getName();

        if ((empty($user->user()) === false) and (empty($lastUsed) === false) and
            (($currentTime - $lastUsed) > $inActivityTime))
        {
            $userEmail = $user->user()->email ?? '';

            $user->logout();

            // if session's value is not explicitly removed
            // then it'll remain even after user is logged out
            Session::forget(UserConstants::TWO_FA_VERIFIED);

            Session::forget(UserConstants::OAUTH_LOGIN);

            $path = '/#/access/signin';

            if (empty($userEmail) === false)
            {
                $path .= '?email=' . $userEmail;
            }

            $response = AppResponse::unauthorizedResponse('Unauthorized.', $routeName, $path);

            $response->withCookie(cookie('rzp_user_email', Crypt::encrypt($userEmail), $sessionConfig['lifetime']));

            return $response;
        }

        return $next($request);
    }
}

