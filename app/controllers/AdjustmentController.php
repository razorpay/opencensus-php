<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Settlement;
use Models\Transaction;

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

        $data = (new Settlement\Service)->getSettlements($input);

        return ApiResponse::json($data);
    }
}