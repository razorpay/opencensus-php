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
        // ALLOW OPTIONS METHOD

        $headers = [
            'Access-Control-Allow-Origin'       => env('AUTH_SERVICE_URL'),
            'Access-Control-Allow-Methods'      => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials'  => true,
        ];

        if ($request->getMethod() === 'OPTIONS')
        {
            return \Response::make('OK', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $header)
        {
            $response->header($key, $header);
        }

        return $response;
    }
}
