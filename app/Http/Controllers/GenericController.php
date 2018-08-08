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
        $allRequestHeaders = Request::header();
        $headers = [];

        foreach($allRequestHeaders as $key => $value) {
            if (stripos($key, 'X-') === 0) {
                $headers[$key] = $value[0];
            }
        }

        $request = new App\Admin\ApiRequestAny([
            'mode' => $mode,
            'headers' => $headers,
        ]);

        $method = Request::method();

        list($error, $data) = $request->send($path, $method);

        return AppResponse::jsonResponse($error, $data);
    }
}
