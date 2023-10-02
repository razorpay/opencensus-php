<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Http\Controllers\Controller;
use RZP\Services\RzpKms\KeyManagementService;

class RzpKmsController extends Controller
{
    public function handleAnyPost($path = null)
    {
        $input = Request::all();

        $data = (new KeyManagementService)->sendRequest($path,'POST',$input);

        return ApiResponse::json($data);
    }

}
