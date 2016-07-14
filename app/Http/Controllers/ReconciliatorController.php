<?php

namespace RZP\Http\Controllers;

use RZP\Reconciliator\Orchestrator;
use RZP\Http\ApiResponse;
use RZP\Exception;
use Request;

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