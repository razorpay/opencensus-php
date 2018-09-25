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

        $lastUsed = $metaDataBag->getLastUsed();
        
        return $next($request);
    }
}

