<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class SettlementController extends Controller
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

    public function postSettlementInitiate($channel = null)
    {
        $input = Input::all();

        $data = (new Settlement\Service)->initiateSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function putEditSettlement($id)
    {
        $input = Input::all();

        $data = (new Settlement\Service)->editSettlement($id, $input);

        return ApiResponse::json($data);
    }

    public function getSettlement($id)
    {
        $data = (new Settlement\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getSettlements()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->fetchMultiple($input);

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

    public function deleteSettlementFile($setlFileType)
    {
        $data = (new Settlement\Service)->deleteSetlFile($setlFileType);

        return ApiResponse::json($data);
    }

    public function getDailySettlement($id)
    {
        $data = (new Settlement\Daily\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getDailySettlements()
    {
        $input = Input::all();

        $data = (new Settlement\Daily\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactions($id)
    {
        $input = Input::all();

        $data = (new Settlement\Service)->fetchSettlementTransactions($id);

        return ApiResponse::json($data);
    }

    public function postSettlementCalculateFees()
    {
        $data = (new Settlement\Daily\Service)->calculatePrevousSettlementFees();

        return ApiResponse::json($data);
    }

    public function postDailySettlementCalculatePreviousFees()
    {
        $data = (new Settlement\Daily\Service)->calculatePreviousDailySettlementFees();

        return ApiResponse::json($data);
    }

    public function getSettlementFixer()
    {
        $data = (new Transaction\Service)->settlementFixer();

        return ApiResponse::json($data);
    }

    public function postComputeDailySettlementServiceTax()
    {
        $data = (new Settlement\Daily\Service)->computeDailySettlementServiceTax();

        return ApiResponse::json($data);
    }

    public function postComputeSettlementServiceTax()
    {
        $data = (new Settlement\Service)->calculatePrevousSettlementServiceTax();

        return ApiResponse::json($data);
    }

    public function getSettlementCombinedReport()
    {
        $input = Input::all();

        $data = (new Settlement\Service)->getSettlementCombinedReport($input);

        return ApiResponse::json($data);
    }
}
