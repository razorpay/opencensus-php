<?php

namespace App\Generic;

use App\Base;
use App\Admin;
use App\Trace\TraceCode;
use Input;
use Config;
use Auth;
use Illuminate\Auth\Access\AuthorizationException;

class Service extends Base\Service
{
    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->adminUser = Auth::guard('api')->user();
    }

    // public function call(array $input, $route, $auth)
    public function call(string $method, array $input)
    {
        $input += ['method' => $method];

        list($auth, $route) = $this->resolveRoute($input);

        list($error, $response) = $this->makeRawApiCall($input, $route, $auth);

        return [$error, $response];
    }

    public function makeRawApiCall($input, $path, $auth)
    {
        $input['mode'] = $input['mode'] ?? (Input::get('mode') ?? 'live');

        $input['auth'] = $auth;

        $input['token'] = session('api_admin.token'); // admin auth token

        $input['file'] = Input::file('file') ?? $input['file'] ?? null;

        if (isset($input['file']))
        {
            $input['file_name'] = Input::get('file_name') ?? $input['file_name'] ?? '';
        }

        $autoBuildQuery = false;

        $request = new Admin\RawApiRequest($input, $path, $autoBuildQuery);

        return $request->send();
    }

    public function makeRawApiCallInternal($input, $path)
    {
        $input['mode'] = Input::get('mode') ?? 'live';

        $input['auth'] = 'internal'; // app auth

        $input['file'] = null;

        $autoBuildQuery = false;
        $this->trace->info(TraceCode::MISC_TRACE_CODE, $input);

        $request = new Admin\RawApiRequest($input, $path, $autoBuildQuery);

        return $request->send();
    }

    /*
        Resolvers and Helpers
    */

    private function resolveRoute($input)
    {
        $routeName = $input['route_name'] ?? null;

        if (empty($routeName) === true)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Route mapping not found',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $routeMap = Config::get('api-route-map');

        foreach ($routeMap as $auth => $routes)
        {
            if (isset($routes[$routeName]))
            {
                $route = $routes[$routeName];

                break;
            }
        }

        if (! isset($route))
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                'Route mapping not found',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        /**
         * 2 different formats:
         * 'payment_fetch_multiple' => 'payments'
         *
         * 'payment_fetch_multiple' => [
         *      'url'       => 'payments',
         *      'routeName' => 'get_payments'
         * ],
         */
        if (is_array($route))
        {
            $endpointUrl = $route['url'];

            // Permission checker for merchant users
            if ( isset($route['routeName']) && empty($this->adminUser) )
            {
                $routeName = $route['routeName'];

                if (\Gate::has($routeName) and \Gate::denies($routeName))
                {
                    throw new AuthorizationException("Unauthorized action");
                }
            }
        }
        else
        {
            $endpointUrl = $route;
        }

        $route = $this->parseUrlParams($endpointUrl, $input);

        return [ $auth, $route ];
    }

    private function parseUrlParams($route, $input)
    {

        // Logic to parse URL Params
        // Eg: /orgs/{id} becomes /orgs/6dLbNSpv5XbCOG (actual ID passed in `url_params`)

        // Will be JSON string / array
        $urlParams = $input['url_params'] ?? [];

        if (! is_array($urlParams))
        {
            $urlParams = json_decode($urlParams, true) ?: [];
        }

        // Check if the route is for an orgs/... related API call
        $pos = strpos($route, '{orgId}');

        if ($pos !== false and !isset($urlParams['{orgId}']))
        {
            $urlParams['{orgId}'] = Auth::guard('api')->user()->org_id;
        }

        if (! empty($urlParams))
        {
            $keys = array_keys($urlParams);
            $vals = array_values($urlParams);

            $vals = array_map('basename', $vals);

            $route = str_replace($keys, $vals, $route);
        }

        return $route;
    }
}
