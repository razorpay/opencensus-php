<?php

namespace App\Http\Middleware;

use Closure;

class SetCspHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->getCspPolicy());

        return $response;
    }

    protected function getCspPolicy()
    {
        $env = \App::environment();

        if ($env === 'production')
        {
            return 'frame-ancestors self https://*.razorpay.com';
        }

        return 'frame-ancestors self https://*.razorpay.in';
    }
}
