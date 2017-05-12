<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Promotion;

class PromotionController extends Controller
{
    public function createPromotion()
    {
        $input = Request::all();

        $data = (new Promotion\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function updatePromotion(string $id)
    {
        $input = Request::all();

        $data = (new Promotion\Service)->update($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchPromotions()
    {
        $input = Request::all();

        $data = (new Promotion\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchPromotionById(string $id)
    {
        $data = (new Promotion\Service)->fetch($id);

        return ApiResponse::json($data);
    }
}
