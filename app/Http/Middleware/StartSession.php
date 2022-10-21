<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Middleware\StartSession as BaseMiddleware;

/**
 * Added this wrapper class for graphql server
 * StartSession loads Session class onto Request object,
 * and adds cookies in response
 *
 * By overriding the handle method preventing addCookiesToResponse
 * from being called
 *
 * Cookies will be forwarded by downstream Graphql service
 */
class StartSession extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        $session = $this->getSession($request);

        if ($this->manager->shouldBlock() ||
            ($request->route() instanceof Route && $request->route()->locksFor())) {
            return $this->handleRequestWhileBlocking($request, $session, $next);
        }

        return $this->handleStatefulRequest($request, $session, $next);
    }

    /**
     * Handle the given request within session state.
     *
     * @param Request  $request
     * @param  Session $session
     * @param Closure  $next
     *
     * @return mixed
     */
    protected function handleStatefulRequest(Request $request, $session, Closure $next)
    {
        // If a session driver has been configured, we will need to start the session here
        // so that the data is ready for an application. Note that the Laravel sessions
        // do not make use of PHP "native" sessions in any way since they are crappy.
        $request->setLaravelSession(
            $this->startSession($request, $session)
        );

        $this->collectGarbage($session);

        $response = $next($request);

        $this->storeCurrentUrl($request, $session);

        // Again, if the session has been configured we will need to close out the session
        // so that the attributes may be persisted to some storage medium. We will also
        // add the session identifier cookie to the application response headers now.
        $this->saveSession($request);

        return $response;
    }
}
