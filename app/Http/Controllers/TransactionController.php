<?php

namespace RZP\Http\Controllers;

use RZP\Models\Transaction;
use ApiResponse;
use Request;
use RZP\Models\Transaction\FeeBreakup;

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

    public function fixSettled($entity)
    {
        $input = Request::all();

        $response = $this->service()->fixSettled($entity, $input);

        return ApiResponse::json($response);
    }

    public function toggleTransactionHold()
    {
        $input = Request::all();

        $response = $this->service()->toggleTransactionHold($input);

        return ApiResponse::json($response);
    }

    public function toggleTransactionRelease()
    {
        $input = Request::all();

        $response = $this->service()->toggleTransactionRelease($input);

        return ApiResponse::json($response);
    }

    public function createCreditRepaymentTransaction()
    {
        $input = Request::all();

        $response = $this->service()->createCreditRepaymentTransaction($input);

        return ApiResponse::json($response);
    }

    public function createCapitalTransaction()
    {
        $input = Request::all();

        $response = $this->service()->createCapitalTransaction($input);

        return ApiResponse::json($response);
    }

    public function createMultipleCapitalTransactions()
    {
        $input = Request::all();

        $response = $this->service()->createMultipleCapitalRepaymentTransactions($input);

        return ApiResponse::json($response);
    }

    public function postInternalTransaction()
    {
        $input = Request::all();

        $response = $this->service()->postInternalTransaction($input);

        return ApiResponse::json($response);
    }

    public function postInternalTransactionCron()
    {
        $input = Request::all();

        $response = $this->service()->postInternalTransactionCron($input);

        return ApiResponse::json($response);
    }

    public function list()
    {
        $input = Request::all();

        $entities = $this->service()->fetchMultiple($input);

        return ApiResponse::json($entities);
    }

    public function createFeesBreakupPartition()
    {
        $response = $this->service()->createFeesBreakupPartition();

        return ApiResponse::json($response);
    }
}
