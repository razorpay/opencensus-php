<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Exception;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator;

class ReconciliatorController extends Controller
{
    protected $service = Reconciliator\Service::class;

    public function postReconciliation()
    {
        $input = Request::all();

        $summary = $this->service()->initiateReconciliationProcess($input);

        return ApiResponse::generateResponse($summary);
    }

    public function postReconciliateCancelledTransactions($gateway)
    {
        $summary = $this->service()->reconciliateCancelledTransactions($gateway);

        return ApiResponse::generateResponse($summary);
    }
}
