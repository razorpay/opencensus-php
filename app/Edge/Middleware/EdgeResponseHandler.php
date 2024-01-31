<?php namespace App\Edge\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Middleware handler class
 * written for dashboard-backend decomp
 * This middleware is specifically for response handling
 * and should be always the last middleware to execute
 */
class EdgeResponseHandler {

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

        $responseHeaders = app('edgeResponseForwarder')->getHeaders();

        if($response instanceof StreamedResponse)
        {
            foreach ($responseHeaders as $key => $value)
            {
                $response->headers->set($key, $value);
            }

            return $response;
        }

        $response->withHeaders($responseHeaders);

        app('edgeMismatchRecorder')->recordMismatches($request);

        return $response;
    }
}
