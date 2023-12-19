<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\ChargeCollections;
use RZP\Models\ChargeCollections\Constants;

class ChargeCollectionsInternalController extends Controller
{
    protected $service = ChargeCollections\Service::class;

    public function createInternalTransaction()
    {
        $input = Request::all();

        $response = [];

        try {
            $txn = $this->service()->createInternalTransaction($input);

            $response[Constants::RESPONSE] = $txn->toArrayPublic();

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::CHARGE_COLLECTIONS_TRANSACTION_CREATE_FAILED);

            $response[Constants::ERROR][Constants::CODE] = $e->getCode();
            $response[Constants::ERROR][Constants::MESSAGE] = $e->getMessage();

            return ApiResponse::json($response);
        }

        return ApiResponse::json($response);
    }
}
