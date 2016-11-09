<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use Config;
use App;
use App\Generic;

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
        
        $input['auth'] = 'admin';

        $routeName = Input::get('route_name');

        unset($input['route_name']);

        $route = Config::get('api-route-map.'.$routeName);

        list($error, $data) = (new Generic\Service)->call($input, $route);
    }

}
