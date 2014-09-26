<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Settlement;
use Models\Transaction;

class SettlementController extends BaseController
{
    public function postGatewayMprReconcile()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->gatewayMprReconcile($input);

        return ApiResponse::json($data);
    }

    public function postGatewayMprGenerate()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->gatewayMprGenerate($input);

        return ApiResponse::json($data);
    }

    public function getTransactionRecords()
    {
        $input = Input::all();

        $data = (new Transaction\Service)->getTransactionRecords($input);

        return ApiResponse::json($data);
    }

    public function getTransactionRecord($id)
    {
        $data = (new Transaction\Service)->getTransactionRecordById($id);

        return ApiResponse::json($data);
    }

    public function postSettlementInitiate()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->initiateSettlements($input);

        return ApiResponse::json($data);
    }

    public function getSettlement($id)
    {
        $data = (new Settlement\Service)->getSettlement($id);

        return ApiResponse::json($data);
    }

    public function getSettlements()
    {
        $input = Input::all();

        $settlements = (new Settlement\Service)->getSettlements($input);

        return ApiResponse::json($settlements);
    }
}