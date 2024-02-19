<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Request;
use RZP\Models\LedgerOutbox\Constants;
use RZP\Trace\TraceCode;
use Twirp\ErrorCode;

class LedgerOutboxController extends Controller {

    public function postRetryFailedReverseShadowTransactions()
    {
        $input = Request::all();

        $cronType = isset($input['type']) === true ? $input['type'] : "";

        $this->trace->info(TraceCode::PG_LEDGER_OUTBOX_FETCH,
            [
                Constants::SOURCE                   => Constants::CRON,
                Constants::CRON_TYPE                => $cronType
            ]
        );

        switch ($cronType) {
            case Constants::TRANSFER:
                $data = $this->service()->retryFailedReverseShadowTransferTransactions($input);
                break;
            case Constants::ONDEMAND_SETTLEMENT:
                $data = $this->service()->retryFailedReverseShadowSettlementOndemandTransactions($input);
                break;
            default: // All other types
                $data = $this->service()->retryFailedReverseShadowTransactions($input);
                break;
        }

        return ApiResponse::json($data);
    }

    public function createLedgerOutboxPartition(): JsonResponse
    {
        $response = $this->service()->createLedgerOutboxPartition();

        return Response::json($response);
    }

    public function createMissingTransactionsForReverseShadowRefunds()
    {
        $input = Request::all();

        $data = $this->service()->createMissingTransactionsForReverseShadowRefunds($input);

        return ApiResponse::json($data);
    }

    public function createMissingTransactionsForReverseShadowAdjustments()
    {
        $input = Request::all();

        $data = $this->service()->createMissingTransactionsForReverseShadowAdjustments($input);

        return ApiResponse::json($data);
    }
}
