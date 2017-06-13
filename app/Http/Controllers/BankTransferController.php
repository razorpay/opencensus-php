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

        $this->service = BankTransfer\Service::class;
    }

    public function validateBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->validate($input);

        return ApiResponse::json($response);
    }

    public function notifyBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->notify($input);

        return ApiResponse::json($response);
    }
}
