<?php

namespace RZP\Http\Middleware;

use Closure;
use Config;
use Symfony\Component\HttpFoundation\Request;

class TrustedProxy
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $proxies = $this->getTrustedProxies();

        // See https://symfony.com/doc/current/request/load_balancer_reverse_proxy.html
        $request->setTrustedProxies($proxies, Request::HEADER_X_FORWARDED_AWS_ELB);

        return $next($request);
    }

    /**
     * Return an array of trusted proxy IP addresses.
     *
     * @return array
     */
    protected function getTrustedProxies()
    {
        $trustedProxies = Config::get('trustedproxy.proxies');

        return (array) $trustedProxies;
    }
}
