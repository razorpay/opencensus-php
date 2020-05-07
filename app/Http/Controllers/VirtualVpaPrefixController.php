<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class VirtualVpaPrefixController extends Controller
{
    public function validatePrefix()
    {
        $input = Request::all();

        $response = $this->service()->validate($input);

        return ApiResponse::json($response);
    }

    public function savePrefix()
    {
        $input = Request::all();

        $response = $this->service()->savePrefix($input);

        return ApiResponse::json($response);
    }
}
