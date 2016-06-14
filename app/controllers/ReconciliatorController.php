<?php

use Http\ApiResponse;
use EE\Exception;

class ReconciliatorController extends BaseController
{
    protected $orchestrator;

    public function __construct()
    {
        $this->orchestrator = new Reconciliator\Orchestrator();
    }

    public function postReconciliation()
    {
        $input = Input::all();

        $statusCode = $this->orchestrator->initiateReconciliationProcess($input);

        return ApiResponse::generateResponse([], $statusCode);
    }
}