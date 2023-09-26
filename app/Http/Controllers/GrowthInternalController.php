<?php

namespace RZP\Http\Controllers;

use Razorpay\Trace\Logger as Trace;
use Request;
use ApiResponse;

use RZP\Models\Growth;
use RZP\Trace\TraceCode;

class GrowthInternalController extends Controller
{
    protected $service = Growth\Service::class;

    public function sendPricingBundleEmail()
    {
        $input = Request::all();

        $data = $this->service()->sendPricingBundleEmail($input);

        return ApiResponse::json($data);
    }

    public function addAmountCredits()
    {
        $input = Request::all();

        $data = $this->service()->addAmountCredits($input);

        return ApiResponse::json($data);
    }

    public function createBundleFeeTransaction()
    {
        $input = Request::all();

        $response = [];

        try {
            $txn = $this->service()->createBundleFeeTransaction($input);

            $response[Growth\Constants::RESPONSE] = $txn->toArrayPublic();

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::GROWTH_TRANSACTION_CREATE_FAILED);

            $response[Growth\Constants::ERROR][Growth\Constants::CODE] = $e->getCode();
            $response[Growth\Constants::ERROR][Growth\Constants::MESSAGE] = $e->getMessage();

            return ApiResponse::json($response);
        }

        return ApiResponse::json($response);
    }


    public function assignPricingRuleToMerchant() {
        $input = Request::all();

        $data = $this->service()->assignPricingRuleToMerchant($input);

        return ApiResponse::json($data);
    }
}
