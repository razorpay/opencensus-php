<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use Input;
use Config;

class GuestGeneric {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get Route Name
        $routeName = Input::get('route_name');

        $routeMap = Config::get('api-route-map');

        // Check if routeName does not exists in api-route-map['guest']
        if (in_array($routeName, $routeMap['guest']) === false)
        {
            $response = [
                'success' => false,
                'errors'  => ['Unauthorised']
            ];

            return Response::json($response);
        }

        return $next($request);
    }
}
