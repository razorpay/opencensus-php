<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use ApiResponse;
use Request;

class BankTransferController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->bankTransferService = new BankTransfer\Service;
    }

    public function validateBankTransfer()
    {
        $input = Request::all();

        $response = $this->bankTransferService->validate($input);

        return ApiResponse::json($response);
    }

    public function payBankTransfer()
    {
        $input = Request::all();

        $response = $this->bankTransferService->pay($input);

        return ApiResponse::json($response);
    }
}
