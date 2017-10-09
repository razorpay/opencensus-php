<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PayoutController extends Controller
{
    public function getPayout(string $id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postPayout()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function postPayoutInitiate(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->initiatePayouts($input, $channel);

        return ApiResponse::json($data);
    }

    public function postMerchantPayout()
    {
        $input = Request::all();

        $data = $this->service()->merchantPayout($input);

        return ApiResponse::json($data);
    }
}
