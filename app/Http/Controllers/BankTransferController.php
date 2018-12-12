<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class BankTransferController extends Controller
{
    public function get(string $id)
    {
        $response = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($response);
    }

    public function list()
    {
        $response = $this->service()->fetchMultiple($this->input);

        return ApiResponse::json($response);
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

    public function retryBankTransferRefund()
    {
        $input = Request::all();

        $response = $this->service()->retryBankTransferRefund($input);

        return ApiResponse::json($response);
    }

    public function editPayerBankAccount(string $id)
    {
        $input = Request::all();

        $response = $this->service()->editPayerBankAccount($id, $input);

        return ApiResponse::json($response);
    }

    public function stripPayerBankAccounts()
    {
        $input = Request::all();

        $response = $this->service()->stripPayerBankAccounts($input);

        return ApiResponse::json($response);
    }

    public function insertBankTransfer(string $provider)
    {
        $input = Request::all();

        $response = $this->service()->insert($provider, $input);

        return ApiResponse::json($response);
    }
}
