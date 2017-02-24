<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Payout;

class PayoutController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = new Payout\Service;
    }

    public function getPayout(string $id)
    {
        $data = $this->service->fetch($id);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = $this->service->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postPayout()
    {
        $input = Request::all();

        $data = $this->service->create($input);

        return ApiResponse::json($data);
    }

    public function postPayoutInitiate(string $channel)
    {
        $input = Request::all();

        $data = $this->service->initiatePayouts($input, $channel);

        return ApiResponse::json($data);
    }
}
