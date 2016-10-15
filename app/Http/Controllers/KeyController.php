<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Key;
use Request;

class KeyController extends Controller
{
    public function getKey($id)
    {
        $data = (new Key\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $input = Request::all();

        $data = (new Key\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}
