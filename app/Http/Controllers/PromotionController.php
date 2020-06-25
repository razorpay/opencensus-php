<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PromotionController extends Controller
{
    use Traits\HasCrudMethods;

    public function deactivatePromotion(string $id)
    {
        $entity = $this->service()->deactivatePromotion($id);

        return ApiResponse::json($entity);
    }

    public function createPromotionForEvent()
    {
        $input = Request::all();

        $entity = $this->service()->createPromotionForEvent($input);

        return ApiResponse::json($entity);
    }
}
