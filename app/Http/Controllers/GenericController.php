<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use Config;
use App;
use App\Generic;

use App\Http\AppResponse;

class GenericController extends Controller
{
    protected $guard = 'admin';

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */

    public function postGeneric()
    {
        $input = ['method' => 'post'];

        $route = $this->resolveRoute();

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getGeneric()
    {
        $input = ['method' => 'get'];

        $route = $this->resolveRoute();

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putGeneric()
    {
        $input = ['method' => 'put'];

        $route = $this->resolveRoute();

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

    public function deleteGeneric()
    {
        $input = ['method' => 'delete'];

        $route = $this->resolveRoute();

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

    private function resolveRoute()
    {
        $routeName = Input::get('route_name');

        $route = Config::get('api-route-map.'.$routeName);

        $route = $this->parseUrlParams($route);

        return $route;
    }

    private function parseUrlParams($route)
    {
        // Logic to parse URL Params
        // Eg: /orgs/{id} becomes /orgs/6dLbNSpv5XbCOG (actual ID passed in `url_params`)

        // Will be JSON string
        $urlParams = json_decode(Input::get('url_params'), true) ?: [];

        // Check if the route is for an orgs/... related API call
        $pos = strpos($route, 'orgs');

        if ($pos === 0)
        {
            $urlParams['{id}'] = 'org_'.Auth::guard('api')->user()->org_id;
        }

        if (! empty($urlParams))
        {
            $keys = array_keys($urlParams);
            $vals = array_values($urlParams);

            $route = str_replace($keys, $vals, $route);
        }

        return $route;
    }

}
