<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Transaction;

class TransactionController extends BaseController
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
}