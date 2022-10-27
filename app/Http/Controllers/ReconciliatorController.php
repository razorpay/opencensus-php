<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Exception;
use RZP\Reconciliator;

class ReconciliatorController extends Controller
{
    protected $service = Reconciliator\Service::class;

    public function postReconciliation()
    {
        $input = Request::all();

        $response = $this->service()->initiateReconciliationProcess($input);

        return ApiResponse::generateResponse($response);
    }

    public function postBulkReconciliationViaBatchService()
    {
        $input = Request::all();

        $response = $this->service()->reconcileViaBatchService($input);

        return ApiResponse::json($response);
    }

    public function postReconciliateCancelledTransactions($gateway)
    {
        $input = Request::all();

        $response = $this->service()->reconciliateCancelledTransactions($gateway, $input);

        return ApiResponse::generateResponse($response);
    }

    public function postBulkRefundsReconciliation()
    {
        $input = Request::all();

        $response = $this->service()->reconcileRefundsAfterScroogeRecon($input);

        return ApiResponse::generateResponse($response);
    }

    public function getReconBatches()
    {
        $input = Request::all();

        $data = $this->service()->getReconBatchesAndFiles($input);

        return ApiResponse::json($data);
    }

    public function getReconFiles()
    {
        $input = Request::all();

        $data = $this->service()->getReconFilesCount($input);

        return ApiResponse::json($data);
    }

    public function updateUpiReconciliationData()
    {
        $input = Request::all();

        $response = $this->service()->updateReconciliationData($input);

        return ApiResponse::generateResponse($response);
    }

    public function testMailgunFlow(){

        $input = Request::all();

        $response = $this->service()->getMailgunSource($input);
        // $response = null;

        return ApiResponse::generateResponse($response);
    }
}
