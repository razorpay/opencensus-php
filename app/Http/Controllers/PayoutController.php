<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PayoutController extends Controller
{
    public function postFundAccountPayout()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayout($input);

        return ApiResponse::json($data);
    }

    public function postFundAccountOnInternalContact()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayoutOnInternalContact($input);

        return ApiResponse::json($data);
    }

    /**
     * Logged in business banking user creates payout with OTP (proxy auth)
     *
     * @return \Illuminate\Http\Response
     */
    public function postFundAccountPayoutWithOtp()
    {
        $input = Request::all();

        $response = $this->service()->fundAccountPayoutWithOtp($input);

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
        $response = $this->service()->rejectFundAccountPayout($id, $this->input);

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

    public function processInitiateForQueuedPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processInitiateForQueuedPayouts($input);

        return ApiResponse::json($data);
    }

    /**
     * TODO : Remove this code. Has been kept here for backward compatibility
     *
     * @return mixed
     */
    public function processDispatchForQueuedPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processDispatchForQueuedPayouts($input);

        return ApiResponse::json($data);
    }

    public function cancelPayout(string $payoutId)
    {
        $input = Request::all();

        $data = $this->service()->cancelPayout($payoutId, $input);

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

    /**
     * @return mixed
     */
    public function approvePayoutBulk()
    {
        $input = Request::all();

        $response = $this->service()->approveBulkPayout($input);

        return ApiResponse::json($response);
    }

    public function calculateEsOnDemandFees()
    {
        $input = Request::all();

        $response = $this->service()->calculateEsOnDemandFees($input);

        return ApiResponse::json($response);
    }

    public function updateTestPayoutStatus(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateTestPayoutStatus($id, $input);

        return ApiResponse::json($response);
    }

    public function processInitiateForBatchSubmittedPayouts()
    {
        $input = Request::all();

        $response = $this->service()->processInitiateForBatchSubmittedPayouts($input);

        return ApiResponse::json($response);
    }

    public function processInitiateForScheduledPayouts()
    {
        $input = Request::all();

        $response = $this->service()->processInitiateForScheduledPayouts($input);

        return ApiResponse::json($response);
    }

    public function getScheduleSlotsForPayouts()
    {
        $response = $this->service()->getScheduleSlotsForPayouts();

        return ApiResponse::json($response);
    }
}
