<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CacheControl
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
    
        if($response instanceof StreamedResponse) {
            
            $response->headers->set('Cache-Control', 'no-store, no-cache');
            
            return $response;
        }
        // Avoids storing local cached response copies on the browser
        $response->header('Cache-Control', 'no-store, no-cache');

        return $response;
    }
}
