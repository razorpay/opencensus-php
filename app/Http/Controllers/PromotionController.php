<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Promotion;

class PromotionController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = (new Promotion\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $data = (new Promotion\Service)->update($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchMultiple()
    {
        $input = Request::all();

        $data = (new Promotion\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchById(string $id)
    {
        $data = (new Promotion\Service)->fetch($id);

        return ApiResponse::json($data);
    }
}
