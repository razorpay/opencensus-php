<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Response;
use Input;
use Config;

class GenericNoAuth {
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

        // Check if routeName does not exists in api-route-map['noauth']
        if (in_array($routeName, $routeMap['noauth']) === false)
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
