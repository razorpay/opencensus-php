<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Settlement;

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
        $data = (new Settlement\Service)->gatewayMprGenerate();

        return ApiResponse::json($data);
    }

    public function getLedgerRecords()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->getLedgerRecords($input);

        return ApiResponse::json($data);
    }

    public function getLedgerRecord($id)
    {
        $data = (new Settlement\Service)->getLedgerRecord($id);

        return ApiResponse::json($data);
    }

    public function sendSettlements()
    {
        (new Settlement\Service)->sendSettlements();
    }
}