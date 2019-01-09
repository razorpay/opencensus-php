<?php

namespace App\Http\Middleware;

use Auth;
use Session;
use Closure;
use App\Http\AppResponse;
use Illuminate\Contracts\Auth\Guard;


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

        $currentTime = time();

        $routeName = $request->route()->getName();

        if ((empty($user->user()) === false) and (empty($lastUsed) === false) and (
            ($currentTime - $lastUsed) > $inActivityTime))
        {
            $userEmail = $user->user()->email ?? '';

            $user->logout();

            $path = '/#/access/signin';

            if (empty($userEmail) === false)
            {
                $path .= '?email=' . $userEmail;
            }

            return AppResponse::unauthorizedResponse('Unauthorized.', $routeName, $path);
        }

        return $next($request);
    }
}

