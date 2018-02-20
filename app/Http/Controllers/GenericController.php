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
    public function handleAny($mode, $path)
    {
        $request = new App\Admin\ApiRequestAny([
            'mode' => $mode
        ]);

        $method = Request::method();

        list($error, $data) = $request->send($path, $method);

        return AppResponse::jsonResponse($error, $data);
    }
}
