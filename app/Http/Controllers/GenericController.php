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
    const WHITELISTED_HEADERS = [
        'x-consumer',
        'x-report-type',
    ];

    public function handleAny($mode, $path)
    {
        $allRequestHeaders = Request::header();
        $headers = [];

        foreach($allRequestHeaders as $key => $value) {
            if (in_array($key, self::WHITELISTED_HEADERS)) {
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
