<?php

namespace App\Http\Middleware;

use Closure;

class Cors
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
        $headers = [
            'Access-Control-Allow-Origin'       => config('oauth.auth_service_url'),
            'Access-Control-Allow-Methods'      => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Credentials'  => 'true',
            'Access-Control-Allow-Headers'      => 'X-Requested-With'
        ];

        //
        // For an OPTIONS pre-flight request, simply return a 200
        // with the above headers
        //
        if ($request->getMethod() === 'OPTIONS')
        {
            return \Response::json([], 200, $headers);
        }

        $response = $next($request);

        //
        // For GET/POST requests, add CORS headers before sending
        // the response
        //
        foreach ($headers as $key => $header)
        {
            $response->header($key, $header);
        }

        return $response;
    }
}
