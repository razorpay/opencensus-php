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

    public function receiveWebhook()
    {
        $input = Input::all();

        $statusCode = $this->orchestrator->baseEntry($input);

        return ApiResponse::generateResponse([], $statusCode);
    }
}