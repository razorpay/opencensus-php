<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Models\Receiver;
use ApiResponse;
use Request;

class BankTransferController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->bankTransferService = new BankTransfer\Service;

        $this->receiverService = new Receiver\Service;
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

    public function createCustomerBankAccount($id)
    {
        $response = $this->receiverService->createCustomerBankAccount($id);

        return ApiResponse::json($response);
    }

    public function createStandingBankAccount()
    {
        $input = Request::all();

        $response = $this->receiverService->createStandingBankAccount($input);

        return ApiResponse::json($response);
    }
}
