<?php

namespace App\Http\Middleware;

use Session;
use Auth;
use Closure;
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

        $inActivityTime = $sessionConfig['in_activity_time'] * 60;

        $dayInactiveTime = $sessionConfig['in_activity_time_day'] * 60;

        $currentTime = time();

        if ((empty($user) === false) and (empty($lastUsed) === false) and (
            ($currentTime - $lastUsed) > $inActivityTime) and (($currentTime - $lastUsed) < $dayInactiveTime))
        {
            $user->logout();

            if ($request->ajax() === true)
            {
                return response('Unauthorized.', 401);
            }
            else
            {
                return redirect()->guest('/');
            }
        }

        return $next($request);
    }
}

