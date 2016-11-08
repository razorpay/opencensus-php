<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Transaction;

class TransactionController extends Controller
{
    public function getTransactions()
    {
        $input = Request::all();

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
        $input = Request::all();

        $data = (new Transaction\Service)->getReport($input);

        return ApiResponse::json($data);
    }
}
