<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use Razorpay\Trace\Logger as Trace;

use RZP\Http\Request\Requests;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Payout\Entity;
use RZP\Models\Feature;

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

    public function deductCreditsViaPayoutService()
    {
        $input = Request::all();

        $data = $this->service()->deductCreditsViaPayoutService($input);

        return ApiResponse::json($data);
    }

    public function fetchPricingInfoForPayoutService()
    {
        $input = Request::all();

        $data = $this->service()->fetchPricingInfoForPayoutService($input);

        return ApiResponse::json($data);
    }

    public function postFundAccountPayout()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayout($input);

        return ApiResponse::json($data);
    }

    public function postCompositePayoutWithOtp()
    {
        $input = Request::all();

        $response = $this->service()->postCompositePayoutWithOtp($input);

        return ApiResponse::json($response);
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

    public function postFundAccountPayout2faForIciciCa()
    {
        $input = Request::all();

        $response = $this->service()->fundAccountPayout2faForIciciCa($input);

        return ApiResponse::json($response);
    }

    public function postApproveFundAccountPayout(string $id)
    {
        $response = $this->service()->approveFundAccountPayout($id, $this->input);

        return ApiResponse::json($response);
    }

    public function postApproveIciciCaFundAccountPayout()
    {
        $response = $this->service()->approveIciciCaFundAccountPayout($this->input);

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
            $response = $this->service()->sendPendingPayoutAndPayoutLinkApprovalEmails();

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

    public function pendingPayoutApprovalReminder()
    {
        try
        {
            $input = Request::all();

            $response = $this->service()->sendPendingPayoutApprovalReminder($input);

            return ApiResponse::json($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PENDING_PAYOUT_APPROVAL_REMINDER_FAILED
            );
        }
    }

    public function pendingPayoutPushNotification()
    {
        try
        {
            $input = Request::all();

            $response = $this->service()->sendPendingPayoutApprovalReminder($input);

            return ApiResponse::json($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PENDING_PAYOUT_APPROVAL_REMINDER_FAILED
            );
        }
    }

    public function sendPendingPayoutsNotificationToSlack()
    {
        $response = $this->service()->sendPendingPayoutsNotificationToSlack();

        return ApiResponse::json($response);
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

    public function ownerBulkRejectPayouts()
    {
        $response = $this->service()->ownerBulkRejectPayouts($this->input);

        return ApiResponse::json($response);
    }

    public function fetchPendingPayoutsSummary()
    {
        $response = $this->service()->fetchPendingPayoutsSummary($this->input);

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

    public function getWorkflowSummaryByType()
    {
        $input = Request::all();

        $data = $this->service()->getWorkflowSummaryByType($input);

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

        $this->trackBulkPayoutErrors($response);

        return ApiResponse::json($response);
    }

    protected function trackBulkPayoutErrors(array $response): void
    {
        try {
            $count = $response['count'];

            if ($count > 0)
            {
                $items = $response['items'];

                if (empty($items) === false)
                {
                    $errorObj = $response['error'];

                    if (empty($errorObj) === false)
                    {
                        $errorDescription = $errorObj['description'];

                        if (empty($errorDescription) === false)
                        {
                            $this->app['trace']->info(
                                TraceCode::BULK_PAYOUTS_ERROR_DESCRIPTION,
                                [
                                    'error' =>  $errorObj,
                                ]
                            );

                            $dimensions = [
                                Metric::ERROR_DESCRIPTION => $errorDescription,
                            ];

                            $this->trace->count(Metric::BULK_PAYOUT_ERROR_DESCRIPTION_COUNT, $dimensions);
                        }
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ERROR_TRIGGERING_METRIC_FOR_BULK_PAYOUTS
            );
        }
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

    public function processDispatchForPayoutsAutoRejectionOnExpiry()
    {
        $input = Request::all();

        $response = $this->service()->processDispatchForPayoutsAutoExpiry($input);

        return ApiResponse::json($response);
    }

    public function dispatchStuckPayouts()
    {
        $input = Request::all();

        $response = $this->service()->dispatchStuckPayouts($input);

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

    public function postFreePayoutMigration()
    {
        $input = Request::all();

        $response = $this->service()->postFreePayoutMigration($input);

        return ApiResponse::json($response);
    }

    public function freePayoutRollback()
    {
        $input = Request::all();

        $response = $this->service()->postFreePayoutRollback($input);

        return ApiResponse::json($response);
    }

    public function payoutSourceUpdate()
    {
        $input = Request::all();

        $response = $this->service()->payoutSourceUpdate($input);

        return ApiResponse::json($response);
    }

    public function statusDetailsSourceUpdate()
    {
        $input = Request::all();

        $response = $this->service()->statusDetailsSourceUpdate($input);

        return ApiResponse::json($response);
    }

    public function getSampleFileForBulkPayouts()
    {
        $input = Request::all();

        $response = $this->service()->getSampleFileForBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function getTemplateFileForBulkPayouts()
    {
        $input = Request::all();

        $response = $this->service()->getTemplateFileForBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function processPayoutsBatch(string $batchId)
    {
        $input = Request::all();

        $response = $this->service()->processPayoutsBatch($batchId, $input);

        return ApiResponse::json($response);
    }

    public function getBatchRows(string $batchId)
    {
        $input = Request::all();

        $response = $this->service()->getBatchRows($batchId, $input);

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

        if (isset($input['merchant_id']))
        {
            $this->trace->info(TraceCode::PAYOUT_UPDATE_MANUAL_FOR_WHATSAPP,
                [
                    'merchant_id' => $input['merchant_id'],
                ]
            );

            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            unset($input['merchant_id']);

            if ($merchant->isFeatureEnabled(Feature\Constants::MERCHANT_ROUTE_WA_INFRA) === true)
            {
                $config = $this->app['config']->get('applications.api_whatsapp');

                $key = $config['key'];

                $secret = $config['secret'];

                $authorization = base64_encode($key . ':' . $secret);

                $headers = ['Content-Type' => 'application/json'];

                $headers['Authorization'] = 'Basic ' . $authorization;

                $method = Request::method();

                $resp = Requests::request("https://api-whatsapp.razorpay.com/" . Request::path(), $headers, json_encode($input), $method);

                $this->trace->info(TraceCode::PAYOUT_UPDATE_MANUAL_FOR_WHATSAPP,
                    [
                        'response' => $resp
                    ]
                );

                return ApiResponse::json($resp);
            }
        }

        $response = $this->service()->updatePayoutStatusManuallyInBatch($input);

        return ApiResponse::json($response);
    }

    public function processDispatchForOnHoldPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processDispatchForOnHoldPayouts($input);

        return ApiResponse::json($data);
    }

    public function processDispatchPartnerBankOnHoldPayouts()
    {
        $input = Request::all();

        $data = $this->service()->processDispatchPartnerBankOnHoldPayouts($input);

        return ApiResponse::json($data);
    }

    public function payoutsServiceCreateFailureProcessingCron()
    {
        $input = Request::all();

        $data = $this->service()->payoutsServiceCreateFailureProcessingCron($input);

        return ApiResponse::json($data);
    }

    public function payoutsServiceUpdateFailureProcessingCron()
    {
        $input = Request::all();

        $data = $this->service()->payoutsServiceUpdateFailureProcessingCron($input);

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

    public function updatePayoutEntry(string $payoutId)
    {
        $input = Request::all();

        $data = $this->service()->updatePayoutEntry($payoutId, $input);

        return ApiResponse::json($data);
    }

    public function getPayoutStatusReasonMap()
    {
        $data = $this->service()->getPayoutStatusReasonMap();

        return ApiResponse::json($data);
    }

    public function getOnHoldMerchantSlas()
    {
        $input = Request::all();

        $data = $this->service()->getOnHoldMerchantSlas($input);

        return ApiResponse::json($data);
    }

    public function updateMerchantOnHoldSlas()
    {
        $input = Request::all();

        $data = $this->service()->updateMerchantOnHoldSlas($input);

        return ApiResponse::json($data);
    }

    public function decrementFreePayoutsForPayoutsService()
    {
        $input = Request::all();

        $data = $this->service()->decrementFreePayoutsForPayoutsService($input);

        return ApiResponse::json($data);
    }

    public function getHolidayDetails()
    {
        $input = Request::all();

        $data = $this->service()->getHolidayDetails($input);

        return ApiResponse::json($data);
    }

    public function fetchPayoutsDetailsForDcc()
    {
        $input = Request::all();

        $data = $this->service()->fetchPayoutsDetailsForDcc($input);

        return ApiResponse::json($data);
    }

    public function initiatePayoutsConsistencyCheck()
    {
        $data = $this->service()->initiatePayoutsConsistencyCheck();

        return ApiResponse::json($data);
    }

    // upload a new file as attachment for the payout
    public function uploadAttachment()
    {
        $input = Request::all();

        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No file Uploaded'
            );
        }

        return ApiResponse::json($this->service()->uploadAttachment($input));
    }

    // get the signed url of the attachment uploaded against the payout
    public function getAttachmentSignedUrl(string $payoutId, string $attachmentId)
    {
        $data = $this->service()->getAttachmentSignedUrl($payoutId, $attachmentId);

        return ApiResponse::json($data);
    }

    // update the attachment on payout
    public function updateAttachments(string $payoutId)
    {
        $input = Request::all();

        return ApiResponse::json($this->service()->updateAttachments($payoutId, $input));
    }

    // sync attachments on payouts linked with payout-links
    public function bulkUpdateAttachments()
    {
        $input = Request::all();

        return ApiResponse::json($this->service()->bulkUpdateAttachments($input));
    }

    // update tax payment ID on payout
    public function updateTaxPayment(string $payoutId)
    {
        $input = Request::all();

        return ApiResponse::json($this->service()->updateTaxPayment($payoutId, $input));
    }

    // prepare and download attachments for the payout report
    public function downloadAttachments()
    {
        $input = Request::all();

        $response = $this->service()->downloadAttachments($input);

        return ApiResponse::json($response);
    }

    // prepare and email attachments for the payout report
    public function emailAttachments()
    {
        $input = Request::all();

        $response = $this->service()->emailAttachments($input);

        if ($response[PayoutConstants::STATUS_CODE] == 200)
        {
            return ApiResponse::json(['Status' => 'Success'], 200);
        }
        else
        {
            return ApiResponse::json(['Status' => 'Failure'], 500);
        }
    }

    // get the details for the payout report's attachment
    public function getReportAttachmentDetails($attachmentId)
    {
        $data = $this->service()->getReportAttachmentDetails($attachmentId);

        return ApiResponse::json($data);
    }

    // get the signed url for the payout report's attachment
    public function getReportAttachmentSignedUrl($attachmentId)
    {
        $data = $this->service()->getReportAttachmentSignedUrl($attachmentId);

        return ApiResponse::json($data);
    }

    public function createTestPayoutsForDowntimeDetectionICICI()
    {
        $input = Request::all();

        $data = $this->service()->createTestPayoutsForDetectingDowntimeICICI($input);

        return ApiResponse::json($data);
    }

    public function createTestPayoutsForDowntimeDetectionYESB()
    {
        $input = Request::all();

        $data = $this->service()->createTestPayoutsForDetectingDowntimeYESB($input);

        return ApiResponse::json($data);
    }

    public function checkTestPayoutsStatus()
    {
        $input = Request::all();

        $data = $this->service()->checkTestPayoutsStatus($input);

        return ApiResponse::json($data);
    }

    public function addBalanceToSourceAccountForTestMerchant()
    {
        $input = Request::all();

        $data = $this->service()->addBalanceToSourceForTestMerchant($input);

        return ApiResponse::json($data);
    }

    public function payoutServiceRedisKeySet()
    {
        $input = Request::all();

        $data = $this->service()->payoutServiceRedisKeySet($input);

        return ApiResponse::json($data);
    }

    public function payoutServiceMailAndSms()
    {
        $input = Request::all();

        $data = $this->service()->payoutServiceMailAndSms($input);

        return ApiResponse::json($data);

    }

    public function payoutServiceDualWrite()
    {
        $input = Request::all();

        $data = $this->service()->payoutServiceDualWrite($input);

        return ApiResponse::json($data);
    }

    public function payoutUpdateByBASRecon()
    {
        $input = Request::all();

        $data = $this->service()->payoutUpdateByBASRecon($input);

        return ApiResponse::json($data);
    }

    public function payoutServiceDeleteCardMetaData()
    {
        $input = Request::all();

        $data = $this->service()->payoutServiceDeleteCardMetaData($input);

        return ApiResponse::json($data);
    }

    public function payoutServiceRenameAttachments($payoutId)
    {
        $input = Request::all();

        return ApiResponse::json($this->service()->payoutServiceRenameAttachments($payoutId, $input));
    }

    public function initiateDataMigration()
    {
        $input = Request::all();

        $data = $this->service()->initiateDataMigration($input);

        return ApiResponse::json($data);
    }

    public function psDataMigrationRedisCleanUp()
    {
        $input = Request::all();

        $data = $this->service()->payoutServiceDataMigrationRedisCleanUp($input);

        return ApiResponse::json($data);
    }

    public function payout2faOtpSendForIciciCa()
    {
        $input = Request::all();

        $response = $this->service()->otpSendForIciciCa2fa($input);

        return ApiResponse::json($response);
    }

    public function validatePayoutsBatch()
    {
        $input = Request::all();

        list($response, $statusCode) = $this->service()->validatePayoutsBatch($input);

        return ApiResponse::json($response, $statusCode);
    }

    public function emailBatchPayoutsSummary(string $batchId, string $merchantId, string $mode)
    {
        list($response, $statusCode) = $this->service()->emailBatchPayoutsSummary($batchId, $merchantId, $mode);

        return ApiResponse::json($response, $statusCode);
    }

    public function getPartnerBankStatus()
    {
        $response = $this->service()->getPartnerBankStatus();

        return ApiResponse::json($response);
    }

    public function caFundManagementPayoutCheck()
    {
        $input = Request::all();

        $data = $this->service()->caFundManagementPayoutCheck($input);

        return ApiResponse::json($data);
    }

    public function getCABalanceManagementConfig(string $merchantId)
    {
        $data = $this->service()->getCABalanceManagementConfig($merchantId);

        return ApiResponse::json($data);
    }

    public function updateCABalanceManagementConfig(string $merchantId)
    {
        $input = Request::all();

        $data = $this->service()->updateCABalanceManagementConfig($input, $merchantId);

        return ApiResponse::json($data);
    }

    public function getSmartRoutingSummary()
    {
        $input = Request::all();

        $data = $this->service()->getSmartRoutingSummary($input);

        return ApiResponse::json($data);
    }

    public function fetchSmartRoutingRulesForMerchant()
    {
        $input = Request::all();

        $data = $this->service()->fetchSmartRoutingRulesForMerchant($input);

        return ApiResponse::json($data);
    }

    public function modifySmartRoutingRulesForMerchant()
    {
        $input = Request::all();

        $data = $this->service()->modifySmartRoutingRulesForMerchant($input);

        return ApiResponse::json($data);
    }
}
