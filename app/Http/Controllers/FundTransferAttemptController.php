<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;
use RZP\Models\FundTransfer\Attempt;

class FundTransferAttemptController extends Controller
{
    public function bulkUpdate()
    {
        $input = Request::all();

        $service = $this->service(Entity::FUND_TRANSFER_ATTEMPT);

        $response = $service->bulkUpdate($input);

        return ApiResponse::json($response);
    }

    public function reconcileFundTransfers(string $channel)
    {
        $input = Request::all();

        $service = $this->service(Entity::FUND_TRANSFER_ATTEMPT);

        $response = $service->reconcileFundTransfers($input, $channel);

        return ApiResponse::json($response);
    }

    public function initiateFundTransfers(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->initiateFundTransfers($input, $channel);

        return ApiResponse::json($data);
    }
}
