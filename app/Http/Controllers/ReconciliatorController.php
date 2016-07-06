<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Exception;

class ReconciliatorController extends Controller
{
    protected $orchestrator;

    public function __construct()
    {
        parent::__construct();

        $this->orchestrator = new Reconciliator\Orchestrator();
    }

    public function postReconciliation()
    {
        $input = Input::all();

        $statusCode = $this->orchestrator->initiateReconciliationProcess($input);

        return ApiResponse::generateResponse([], $statusCode);
    }
}