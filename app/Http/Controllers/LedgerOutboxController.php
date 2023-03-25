<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Request;

class LedgerOutboxController extends Controller {

    public function postRetryFailedReverseShadowTransactions()
    {
        $input = Request::all();

        $data = $this->service()->retryFailedReverseShadowTransactions($input);

        return ApiResponse::json($data);
    }

    public function createLedgerOutboxPartition(): JsonResponse
    {
        $response = $this->service()->createLedgerOutboxPartition();

        return Response::json($response);
    }
}
