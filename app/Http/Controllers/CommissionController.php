<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CommissionController extends Controller
{
    public function list()
    {
        $input = Request::all();

        $data  = $this->service()->list($input);

        return ApiResponse::json($data);
    }
}
