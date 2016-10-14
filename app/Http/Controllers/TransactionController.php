<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Transaction;
use Request;

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

    public function postMigrateOlderTransactions()
    {
        $input = Request::all();

        $data = (new Transaction\Service)->postMigrateOlderTransactions($input);

        return ApiResponse::json($data);
    }
}
