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

        $inActivityTime = $sessionConfig['inactivity_time'] * 60;

        $currentTime = time();

        if ((empty($user) === false) and (empty($lastUsed) === false) and (
            ($currentTime - $lastUsed) > $inActivityTime))
        {
            $userEmail = $user->user()->email ?? '';

            $request->session()->invalidate();

            $user->logout();

            if ($request->ajax() === true)
            {
                return response('Unauthorized.', 401);
            }
            else
            {
                $path = '/#/access/signin';

                if (empty($userEmail) === false)
                {
                    $path .= '?email=' . $userEmail;
                }

                // Need to redirect to home page with email as a param.
                return redirect()->guest($path);
            }
        }

        return $next($request);
    }
}

