<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Key;

class KeyController extends Controller
{
    public function getKey($id)
    {
        $data = (new Key\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $input = Input::all();

        $data = (new Key\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}
