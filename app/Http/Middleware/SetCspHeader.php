<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        
        if($response instanceof StreamedResponse)
        {
            return $response;
        }
        return $response;
    }

    protected function getCspPolicy()
    {
        $env = \App::environment();

        if ($env === 'production') {
            return 'frame-ancestors self https://razorpay.com https://*.razorpay.com';
        }

        return 'frame-ancestors self https://*.razorpay.in';
    }
}
