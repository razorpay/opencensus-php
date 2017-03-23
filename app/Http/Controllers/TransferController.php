<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Transfer;

class TransferController extends Controller
{
    public function getTransfer(string $id)
    {
        $transfer = $this->service('transfer')->fetch($id);

        return ApiResponse::json($transfer);
    }

    public function getTransfers()
    {
        $input = Request::all();

        $transfers = $this->service('transfer')->fetchMultiple($input);

        return ApiResponse::json($transfers);
    }

    public function postTransfer()
    {
        $input = Request::all();

        $transfer = $this->service('transfer')->create($input);

        return ApiResponse::json($transfer);
    }

    public function postTransferReversal(string $id)
    {
        $input = Request::all();

        $reversal = $this->service('transfer')->reverse($id, $input);

        return ApiResponse::json($reversal);
    }

    public function patchTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service('transfer')->edit($id, $input);

        return ApiResponse::json($transfer);
    }
}
