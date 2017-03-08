<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Details;
use RZP\Models\Transaction;

class SettlementController extends Controller
{
    public function postSettlementInitiate($channel = null)
    {
        $input = Request::all();

        $data = (new Settlement\Service)->initiateSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementInitiateV2($channel)
    {
        $input = Request::all();

        $data = (new Settlement\Service)->initiateSettlementsV2($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementRetry($channel)
    {
        $input = Request::all();

        $data = (new Settlement\Service)->processFailedSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementFileGenerate()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->generateSettlementFile($input);

        return ApiResponse::json($data);
    }

    public function putEditSettlement($id)
    {
        $input = Request::all();

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
        $input = Request::all();

        $data = (new Settlement\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcile()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->reconcileSettlements($input);

        return ApiResponse::json($data);
    }

    public function postH2HSettlementReconcile()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->reconcileH2HSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReturn()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->returnSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileGenerate()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->generateSettlementReconciliation($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReturnGenerate()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->generateSettlementReturn($input);

        return ApiResponse::json($data);
    }

    public function deleteSettlementFile($setlFileType)
    {
        $data = (new Settlement\Service)->deleteSetlFile($setlFileType);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactions($id)
    {
        $data = (new Settlement\Service)->fetchSettlementTransactions($id);

        return ApiResponse::json($data);
    }

    public function postSettlementCalculateFees()
    {
        $data = (new FundTransfer\Batch\Service)->calculatePrevousSettlementFees();

        return ApiResponse::json($data);
    }

    public function postBatchSettlementCalculatePreviousFees()
    {
        $data = (new FundTransfer\Batch\Service)->calculatePreviousBatchSettlementFees();

        return ApiResponse::json($data);
    }

    public function getSettlementFixer()
    {
        $data = (new Transaction\Service)->settlementFixer();

        return ApiResponse::json($data);
    }

    public function postComputeBatchSettlementServiceTax()
    {
        $data = (new Settlement\Batch\Service)->computeBatchSettlementServiceTax();

        return ApiResponse::json($data);
    }

    public function postComputeSettlementServiceTax()
    {
        $data = (new Settlement\Service)->calculatePreviousSettlementServiceTax();

        return ApiResponse::json($data);
    }

    public function getSettlementDetails($id)
    {
        $data = (new Settlement\Details\Service)->getSettlementDetails($id);

        return ApiResponse::json($data);
    }

    public function postSettlementDetailsForOldTxns()
    {
        $input = Request::all();

        $data = (new Settlement\Details\Service)->postSettlementDetailsForOldTxns($input);

        return ApiResponse::json($data);
    }

    public function getSettlementCombinedReport()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->getSettlementCombinedReport($input);

        return ApiResponse::json($data);
    }

    public function postInitiateTransfer()
    {
        $input = Request::all();

        $data = (new Settlement\Service)->postInitiateTransfer($input);

        return ApiResponse::json($data);
    }
}
