<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class KeyController extends Controller
{
    public function getKey($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}
