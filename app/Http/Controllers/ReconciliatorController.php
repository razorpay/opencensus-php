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

    public function postReconciliateCancelledTransactions($gateway)
    {
        $response = $this->service()->reconciliateCancelledTransactions($gateway);

        return ApiResponse::generateResponse($response);
    }
}
