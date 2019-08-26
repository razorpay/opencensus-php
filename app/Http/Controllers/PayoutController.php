<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PayoutController extends Controller
{
    public function postFundAccountPayout()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayout($input);

        return ApiResponse::json($data);
    }

    /**
     * Logged in business banking user creates payout with OTP (proxy auth)
     *
     * @return \Illuminate\Http\Response
     */
    public function postFundAccountPayoutWithOtp()
    {
        $response = $this->service()->fundAccountPayoutWithOtp($this->input);

        return ApiResponse::json($response);
    }

    public function postApproveFundAccountPayout(string $id)
    {
        $response = $this->service()->approveFundAccountPayout($id, $this->input);

        return ApiResponse::json($response);
    }

    public function bulkApproveFundAccountPayouts()
    {
        $response = $this->service()->bulkApproveFundAccountPayouts($this->input);

        return ApiResponse::json($response);
    }

    public function postRejectFundAccountPayout(string $id)
    {
        $response = $this->service()->rejectFundAccountPayout($id);

        return ApiResponse::json($response);
    }

    public function bulkRejectFundAccountPayouts()
    {
        $response = $this->service()->bulkRejectFundAccountPayout($this->input);

        return ApiResponse::json($response);
    }

    public function postMerchantPayoutOnDemand()
    {
        $input = Request::all();

        $data = $this->service()->merchantPayoutOnDemand($input);

        return ApiResponse::json($data);
    }

    public function postInternalMerchantPayout()
    {
        $input = Request::all();

        $data = $this->service()->internalMerchantPayout($input);

        return ApiResponse::json($data);
    }

    public function getPayout(string $id)
    {
        $input = Request::all();

        $data = $this->service()->fetch($id, $input);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postPayoutRetry(string $id)
    {
        $data = $this->service()->processReversedPayout($id);

        return ApiResponse::json($data);
    }

    public function getPurposes()
    {
        $data = $this->service()->getPurposes();

        return ApiResponse::json($data);
    }

    public function postPurpose()
    {
        $input = Request::all();

        $data = $this->service()->postPurpose($input);

        return ApiResponse::json($data);
    }

    public function getPayoutReversal(string $payoutId)
    {
        $data = $this->service()->fetchReversalOfPayout($payoutId);

        return ApiResponse::json($data);
    }

    public function getQueuedPayoutsSummary()
    {
        $data = $this->service()->getQueuedPayoutsSummary();

        return ApiResponse::json($data);
    }

    public function getSummary()
    {
        $data = $this->service()->getDashboardSummary();

        return ApiResponse::json($data);
    }

    public function getWorkflowSummary()
    {
        $data = $this->service()->getWorkflowSummary();

        return ApiResponse::json($data);
    }

    public function processDispatchForQueuedPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processDispatchForQueuedPayouts($input);

        return ApiResponse::json($data);
    }

    public function cancelPayout(string $payoutId)
    {
        $data = $this->service()->cancelPayout($payoutId);

        return ApiResponse::json($data);
    }

    /**
     *  Route to create bulk payouts.
     *  Currently it is used by batch Service
     */
    public function createPayoutBulk()
    {
        $input = Request::all();

        $response = $this->service()->createBulkPayout($input);

        return ApiResponse::json($response);
    }
}
