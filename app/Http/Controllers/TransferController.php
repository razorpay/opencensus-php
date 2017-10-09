<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class TransferController extends Controller
{
    public function getTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service()->fetch($id, $input);

        return ApiResponse::json($transfer);
    }

    public function getTransfers()
    {
        $input = Request::all();

        $transfers = $this->service()->fetchMultiple($input);

        return ApiResponse::json($transfers);
    }

    public function getTransferReversals(string $id)
    {
        $reversals = $this->service()->fetchReversalsOfTransfer($id);

        return ApiResponse::json($reversals);
    }

    public function postTransfer()
    {
        $input = Request::all();

        $transfer = $this->service()->create($input);

        return ApiResponse::json($transfer);
    }

    public function postTransferReversal(string $id)
    {
        $input = Request::all();

        $reversal = $this->service()->reverse($id, $input);

        return ApiResponse::json($reversal);
    }

    public function patchTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service()->edit($id, $input);

        return ApiResponse::json($transfer);
    }
}
