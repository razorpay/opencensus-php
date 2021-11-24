<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;

class PayoutController extends Controller
{
    public function createPayoutEntry()
    {
        $input = Request::all();

        $data = $this->service()->createPayoutEntry($input);

        return ApiResponse::json($data);
    }

    public function createWorkflowForPayout()
    {
        $input = Request::all();

        $data = $this->service()->createWorkflowForPayout($input);

        return ApiResponse::json($data);
    }

    public function createFTAForPayoutService(string $payoutId)
    {
        $data = $this->service()->createFTAForPayoutService($payoutId);

        return ApiResponse::json($data);
    }

    public function createPayoutServiceTransaction()
    {
        $input = Request::all();

        $data = $this->service()->createPayoutServiceTransaction($input);

        return ApiResponse::json($data);
    }

    public function postFundAccountPayout()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayout($input);

        return ApiResponse::json($data);
    }

    public function validatePayout()
    {
        $input = Request::all();

        $data = $this->service()->validatePayout($input);

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

    public function postApproveFundAccountPayoutInternal(string $id)
    {
        try
        {
            $response = $this->service()->processActionOnFundAccountPayoutInternal($id, true, $this->input);

            return ApiResponse::json($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_ACTION_VIA_WORKFLOW_SERVICE_FAILED,
                ['payout_id' => $id]);

            // This happens when approve request is received twice via WFS
            // In that scenario, the payout is no longer in pending state
            // Therefore we return HTTP 409
            if ($e->getCode() === ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE)
            {
                $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_DUPLICATE_REQUEST_TOTAL);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS,
                    null,
                    []);
            }

            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);

            list($publicError, $httpStatusCode) =
                ApiResponse::getErrorResponseFields(ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE);

            return ApiResponse::generateResponse($publicError, $httpStatusCode);
        }
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

    public function migrateWorkflowConfigsToWorkflowService()
    {
        $input = Request::all();

        $response = $this->service()->migrateOldConfigToNewOnes($input);

        return ApiResponse::json($response);
    }

    public function postRejectFundAccountPayoutInternal(string $id)
    {
        try
        {
            $response = $this->service()->processActionOnFundAccountPayoutInternal($id, false, $this->input);

            return ApiResponse::json($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_ACTION_VIA_WORKFLOW_SERVICE_FAILED,
                ['payout_id' => $id]);

            // This happens when reject request is received twice via WFS
            // In that scenario, the payout is no longer in pending state
            // Therefore we return HTTP 409
            if ($e->getCode() === ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE)
            {
                $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_DUPLICATE_REQUEST_TOTAL);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS,
                    null,
                    []);
            }

            $this->trace->count(Metric::PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL);

            list($publicError, $httpStatusCode) =
                ApiResponse::getErrorResponseFields(ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE);

            return ApiResponse::generateResponse($publicError, $httpStatusCode);
        }
    }

    /**
     * TODO:
     */
    public function pendingPayoutApprovalEmail()
    {
        try
        {
            $response = $this->service()->sendPendingPayoutApprovalEmails();

            return ApiResponse::json($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PENDING_PAYOUT_APPROVAL_EMAILS_FAILED
                );
        }
    }

    public function bulkRejectFundAccountPayouts()
    {
        $response = $this->service()->bulkRejectFundAccountPayout($this->input);

        return ApiResponse::json($response);
    }

    public function bulkRetryWorkflowOnPayout()
    {
        $response = $this->service()->bulkRetryWorkflowOnPayout($this->input);

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

    public function getPurposesInternal(string $merchantId)
    {
        $data = $this->service()->getPurposesInternal($merchantId);

        return ApiResponse::json($data);
    }

    public function validatePurpose()
    {
        $input = Request::all();

        $data = $this->service()->validatePurpose($input);

        return ApiResponse::json($data);
    }

    public function postPurpose()
    {
        $input = Request::all();

        $data = $this->service()->postPurpose($input);

        return ApiResponse::json($data);
    }

    public function postBulkPurpose(string $merchantId)
    {
        $input = Request::all();

        $data = $this->service()->postBulkPurpose($merchantId, $input);

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

    public function updatePayoutStatusManually(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updatePayoutStatusManually($id, $input);

        return ApiResponse::json($response);
    }

    public function getFreePayoutsAttributes($balanceId)
    {
        $response = $this->service()->getFreePayoutsAttributes($balanceId);

        return ApiResponse::json($response);
    }

    public function getSampleFileForBulkPayouts()
    {
        $input = Request::all();

        $response = $this->service()->getSampleFileForBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function postBulkPayoutsAmountType()
    {
        $input = Request::all();

        $response = $this->service()->postBulkPayoutsAmountType($input);

        return ApiResponse::json($response);
    }

    public function updateBulkPayoutsAmountType()
    {
        $response = $this->service()->updateBulkPayoutsAmountType();

        return ApiResponse::json($response);
    }

    public function updatePayoutStatusManuallyInBatch()
    {
        $input = Request::all();

        $response = $this->service()->updatePayoutStatusManuallyInBatch($input);

        return ApiResponse::json($response);
    }

    public function processDispatchForOnHoldPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processDispatchForOnHoldPayouts($input);

        return ApiResponse::json($data);
    }

    public function processSchedulePayoutOnPayoutService()
    {
        $input = Request::all();

        $data = $this->service()->processSchedulePayoutOnPayoutService($input);

        return ApiResponse::json($data);
    }

    public function retryPayoutsOnPayoutService()
    {
        $input = Request::all();

        $data = $this->service()->retryPayoutsOnPayoutService($input);

        return ApiResponse::json($data);
    }
}
