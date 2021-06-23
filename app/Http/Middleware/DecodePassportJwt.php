<?php


namespace RZP\Http\Middleware;


use Illuminate\Http\Request;
use RZP\Http\Edge\PreAuthenticate;
use Symfony\Component\HttpFoundation\Response;

class DecodePassportJwt
{
    /**
     * Handles http request:
     * - Decodes JWT passport from request
     * - Sets the passport in RequestContextV2
     * - Pushes prom metrics
     *
     * @param  Request  $request
     * @param  \Closure $next
     * @return Response
     * @throws \Throwable
     */
    public function handle($request, \Closure $next)
    {
        (new PreAuthenticate)->handle($request);

        return $next($request);
    }
}
