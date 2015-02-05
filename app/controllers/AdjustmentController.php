<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Adjustment;

class AdjustmentController extends BaseController
{
    public function getAdjustment($id)
    {
        $data = (new Adjustment\Service)->getAdjustment($id);

        return ApiResponse::json($data);
    }

    public function getAdjustments()
    {
        $input = Input::all();

        $data = (new Adjustment\Service)->getAdjustments($input);

        return ApiResponse::json($data);
    }

    public function postAdjustment()
    {
        $input = Input::all();

        $data = (new Adjustment\Service)->addAdjustment($input);

        return ApiResponse::json($data);
    }
}