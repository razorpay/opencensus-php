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

    public function postSettlementInitiate($channel = null)
    {
        $input = Input::all();

        $data = (new Settlement\Service)->initiateSettlements($input, $channel);

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

        $data = (new Settlement\Service)->getSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcile()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->reconcileSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReturn()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->returnSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileGenerate()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->generateSettlementReconciliation($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReturnGenerate()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->generateSettlementReturn($input);

        return ApiResponse::json($data);
    }
}