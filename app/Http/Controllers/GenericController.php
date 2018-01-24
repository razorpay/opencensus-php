<?php
namespace App\Http\Controllers;

use App;
use Auth;
use Input;
use Config;
use Request;
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

    public function handle()
    {
        $method = Request::method();

        $input = Input::all();

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call($method, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function handleAny($mode, $path)
    {
        $request = new App\Admin\ApiRequestAny($mode);

        list($error, $data) = $request->send($path);

        return AppResponse::jsonResponse($error, $data);
    }

    public function handleGuest($path = '/')
    {
        return $this->handleAny($path, null);
    }

    public function handleUser($path)
    {
        return $this->handleAny($path, 'live');
    }
}
