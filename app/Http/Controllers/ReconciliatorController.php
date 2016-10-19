<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Exception;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator;

class ReconciliatorController extends Controller
{
    protected $orchestrator;

    public function __construct()
    {
        parent::__construct();

        $this->orchestrator = new Orchestrator();
    }

    public function postReconciliation()
    {
        $input = Request::all();

        $summary = $this->orchestrator->initiateReconciliationProcess($input);

        return ApiResponse::generateResponse($summary);
    }

    public function postReconciliateCancelledTransactions($gateway)
    {
        $summary = (new Reconciliator\Service)->reconciliateCancelledTransactions($gateway);

        return ApiResponse::generateResponse($summary);
    }
}