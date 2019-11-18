<?php

namespace RZP\Http\Controllers;

use RZP\Models\Transaction;
use ApiResponse;
use Request;

class TransactionController extends Controller
{
    public function postCreateFeeBreakup()
    {
        $input = Request::all();

        $data = $this->service()->createFeeBreakupForTransaction($input);

        return ApiResponse::json($data);
    }

    public function updateMultipleTransactions()
    {
        $input = Request::all();

        $data = $this->service()->updateMultipleTransactions($input);

        return ApiResponse::json($data);
    }

    public function markTransactionPostpaid()
    {
        $input = Request::all();

        $response = $this->service()->markTransactionPostpaid($input);

        return ApiResponse::json($response);
    }

    public function fixSettled()
    {
        $input = Request::all();

        $response = $this->service()->fixSettled($input);

        return ApiResponse::json($response);
    }
}
