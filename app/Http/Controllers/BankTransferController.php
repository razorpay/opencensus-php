<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use ApiResponse;
use Request;

class BankTransferController extends Controller
{
    protected $bankTransferService;

    public function __construct()
    {
        parent::__construct();
    }

    public function validateBankTransfer()
    {
        $input = Request::all();

        $this->bankTransferService = new BankTransfer\Service($input);

        $response = $this->bankTransferService->validate();

        return ApiResponse::json($response);
    }

    public function payBankTransfer()
    {
        $input = Request::all();

        $this->bankTransferService = new BankTransfer\Service($input);

        $response = $this->bankTransferService->pay();

        return ApiResponse::json($response);
    }
}
