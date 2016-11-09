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

    public function __construct()
    {
        $this->admin = Auth::guard('admin')->user();
    }

    public function postGeneric()
    {
        $input['method'] = 'post';

        $routeName = Input::get('route_name');

        unset($input['route_name']);

        $route = Config::get('api-route-map.'.$routeName);

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getGeneric()
    {
        $input['method'] = 'get';

        $routeName = Input::get('route_name');

        unset($input['route_name']);

        $route = Config::get('api-route-map.'.$routeName);

        // Logic to parse URL Params
        // Eg: /orgs/{id} becomes /orgs/6dLbNSpv5XbCOG (actual ID passed in `url_params`)

        $url_params = Input::get('url_params');
        
        if (! empty($url_params))
        {
            $url_params = json_decode($url_params, true);
            $keys = array_keys($url_params);
            $vals = array_values($url_params);

            $route = str_replace($keys, $vals, $route);
        }

        list($error, $data) = (new Generic\Service)->call($input, $route);

        return AppResponse::jsonResponse($error, $data);
    }

}
