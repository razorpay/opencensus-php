<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Payout;
use Request;

class PayoutController extends Controller
{
    public function getPayout($id)
    {
        $data = (new Payout\Service)->getPayout($id);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = (new Payout\Service)->getPayouts($input);

        return ApiResponse::json($data);
    }

    public function postPayout()
    {
        $input = Request::all();

        $data = (new Payout\Service)->postPayout($input);

        return ApiResponse::json($data);
    }
}
