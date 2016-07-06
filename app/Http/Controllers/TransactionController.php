<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use EE\Exception\RecoverableException;
use RZP\Models\Transaction;

class TransactionController extends Controller
{
    public function getTransactions()
    {
        $input = Input::all();

        $data = (new Transaction\Service)->getTransactionRecords($input);

        return ApiResponse::json($data);
    }

    public function getTransaction($id)
    {
        $data = (new Transaction\Service)->getTransactionRecordById($id);

        return ApiResponse::json($data);
    }

    public function getMonthlyReport()
    {
        $input = Input::all();

        $data = (new Transaction\Service)->getReport($input);

        return ApiResponse::json($data);
    }
}
