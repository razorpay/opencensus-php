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

    public function handle($path = '/')
    {
        $request = new App\Admin\ApiRequestAny();

        list($error, $data) = $request->send($path);

        return AppResponse::jsonResponse($error, $data);
    }

    /* Catchall route: /admin/api/{any} */
    public function handleAdmin($auth, $path)
    {
        $request = new App\Admin\ApiRequestAny();

        list($error, $data) = $request->sendWithAdminToken($auth, $path);

        return AppResponse::jsonResponse($error, $data);
    }

    public function handleMerchant($mode, $path)
    {
        $request = new App\Admin\ApiRequestAny();

        list($error, $data) = $request->sendWithMerchantProxy($mode, $path);

        return AppResponse::jsonResponse($error, $data);
    }

}
