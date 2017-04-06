<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use Config;
use App;
use App\Generic;
use Request;

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

    public function handle()
    {
        $method = Request::method();

        $input = Input::all();

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call($method, $input);

        return AppResponse::jsonResponse($error, $data);
    }

}
