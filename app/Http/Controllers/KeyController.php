<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class KeyController extends Controller
{
    public function postCreateKeys()
    {
        $data = $this->service()->createKey();

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $data = $this->service()->fetchKeys();

        return ApiResponse::json($data);
    }

    public function putKeys($keyId)
    {
        $input = Request::all();

        $keys = $this->service()->updateKey($keyId, $input);

        return ApiResponse::json($keys);
    }
}
