<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\BankTransfer;

class BankTransferController extends Controller
{
    protected $service = BankTransfer\Service::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function processBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->process($input);

        return ApiResponse::json($response);
    }

    public function notifyBankTransfer()
    {
        $input = Request::all();

        $response = $this->service()->notify($input);

        return ApiResponse::json($response);
    }

    public function fetchBankTransferForPayment(string $paymentId)
    {
        $input = Request::all();

        $response = $this->service()->fetchBankTransferForPayment($paymentId);

        return ApiResponse::json($response);
    }
}
