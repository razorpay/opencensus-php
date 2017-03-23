<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Payout;

class PayoutController extends Controller
{
    public function getPayout(string $id)
    {
        $data = $this->service('payout')->fetch($id);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = $this->service('payout')->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postPayout()
    {
        $input = Request::all();

        $data = $this->service('payout')->create($input);

        return ApiResponse::json($data);
    }

    public function postPayoutInitiate(string $channel)
    {
        $input = Request::all();

        $data = $this->service('payout')->initiatePayouts($input, $channel);

        return ApiResponse::json($data);
    }
}
