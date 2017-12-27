<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Entity as E;

class SettlementController extends Controller
{
    public function postSettlementInitiate($channel = null)
    {
        $input = Request::all();

        $data = $this->service()->initiateSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementRetry()
    {
        $input = Request::all();

        $data = $this->service()->processFailedSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementFileGenerate()
    {
        $input = Request::all();

        $data = $this->service()->generateSettlementFile($input);

        return ApiResponse::json($data);
    }

    public function putEditSettlement($id)
    {
        $input = Request::all();

        $data = $this->service()->editSettlement($id, $input);

        return ApiResponse::json($data);
    }

    public function getSettlement($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getSettlements()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcile()
    {
        $input = Request::all();

        $data = $this->service()->reconcileSettlements($input);

        return ApiResponse::json($data);
    }

    public function postH2HSettlementReconcile()
    {
        $input = Request::all();

        $data = $this->service()->reconcileH2HSettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileGenerate()
    {
        $input = Request::all();

        $data = $this->service()->generateSettlementReconciliation($input);

        return ApiResponse::json($data);
    }

    public function postReconcileInTestMode()
    {
        $input = Request::all();

        $data = $this->service()->reconcileSettlementsInTestMode($input);

        return ApiResponse::json($data);
    }

    public function deleteSettlementFile($setlFileType)
    {
        $data = $this->service()->deleteSetlFile($setlFileType);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactions($id)
    {
        $data = $this->service()->fetchSettlementTransactions($id);

        return ApiResponse::json($data);
    }

    public function getSettlementFixer()
    {
        $data = $this->service(E::TRANSACTION)->settlementFixer();

        return ApiResponse::json($data);
    }

    public function getSettlementDetails($id)
    {
        $data = $this->service(E::SETTLEMENT_DETAILS)->getSettlementDetails($id);

        return ApiResponse::json($data);
    }

    public function postSettlementDetailsForOldTxns()
    {
        $input = Request::all();

        $data = $this->service(E::SETTLEMENT_DETAILS)->postSettlementDetailsForOldTxns($input);

        return ApiResponse::json($data);
    }

    public function getSettlementCombinedReport()
    {
        $input = Request::all();

        $data = $this->service()->getSettlementCombinedReport($input);

        return ApiResponse::json($data);
    }

    public function postInitiateTransfer()
    {
        $input = Request::all();

        $data = $this->service()->postInitiateTransfer($input);

        return ApiResponse::json($data);
    }

    public function addBeneficiary(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->addBeneficiary($channel, $input);

        return ApiResponse::json($data);
    }
}
