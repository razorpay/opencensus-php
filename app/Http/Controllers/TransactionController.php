<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class TransactionController extends Controller
{
    public function getTransactions()
    {
        $input = Request::all();

        $data = $this->service()->getTransactionRecords($input);

        return ApiResponse::json($data);
    }

    public function getTransaction($id)
    {
        $data = $this->service()->getTransactionRecordById($id);

        return ApiResponse::json($data);
    }

    public function getMonthlyReport()
    {
        $input = Request::all();

        $data = $this->service()->getReport($input);

        return ApiResponse::json($data);
    }

    public function postCreateFeeBreakup()
    {
        $input = Request::all();

        $data = $this->service()->createFeeBreakupForTransaction($input);

        return ApiResponse::json($data);
    }

    public function getEntityTransaction($entity, $id)
    {
        $data = $this->service()->getEntityTransaction($entity, $id);

        return ApiResponse::json($data);
    }
}
