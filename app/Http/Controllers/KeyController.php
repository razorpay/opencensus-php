<?php

use Http\ApiResponse;
use Models\Key;

class KeyController extends BaseController
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