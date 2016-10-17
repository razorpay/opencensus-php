<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Exception;
use RZP\Reconciliator\Orchestrator;

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
}