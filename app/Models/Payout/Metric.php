<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Balance\Entity as Balance;

final class Metric
{
    // Counters
    const PAYOUT_CREATED_TOTAL                                      = 'payout_created_total';
    const PAYOUT_PENDING_TOTAL                                      = 'payout_pending_total';
    const PAYOUT_QUEUED_TOTAL                                       = 'payout_queued_total';
    const PAYOUT_FAILED_TOTAL                                       = 'payout_failed_total';
    const PAYOUT_REVERSED_TOTAL                                     = 'payout_reversed_total';
    const PAYOUT_PROCESSED_TOTAL                                    = 'payout_processed_total';
    const PAYOUT_INITIATED_TOTAL                                    = 'payout_initiated_total';
    const PAYOUT_REJECTED_TOTAL                                     = 'payout_rejected_total';
    const PAYOUT_CANCELLED_TOTAL                                    = 'payout_cancelled_total';
    const ON_HOLD_PAYOUT_FAILED_TOTAL                               = 'on_hold_payout_failed_total';
    const PAYOUT_BATCH_SUBMITTED_TOTAL                              = 'payout_batch_submitted_total';
    const PAYOUT_SCHEDULED_TOTAL                                    = 'payout_scheduled_total';
    const PAYOUT_ON_HOLD_TOTAL                                      = 'payout_on_hold_total';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TOTAL                     = 'payout_create_request_submitted_total';
    const PAYOUT_WORKFLOW_CREATION_FAILED_TOTAL                     = 'payout_workflow_creation_failed_total';
    const PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL                       = 'payout_workflow_action_failed_total';
    const PAYOUT_WORKFLOW_ACTION_DUPLICATE_REQUEST_TOTAL            = 'payout_workflow_action_duplicate_request_total';
    const ON_HOLD_PAYOUT_CHECK_FAILED                               = 'on_hold_payout_check_failed';
    const CREDIT_TRANSFER_FOR_VA_TO_VA_PAYOUT_FAILURE               = 'credit_transfer_for_va_to_va_payout_failure';
    const PAYOUT_PUBLIC_ERROR_CODE_UNMAPPED_BANK_STATUS_CODE        = 'payout_public_error_code_unmapped_bank_status_code';
    const PAYOUT_METRIC_PUSH_EXCEPTION_COUNT                        = 'payout_metric_push_exception_count';
    const FTS_OTP_CREATION_FAILURES_COUNT                           = 'fts_otp_creation_failures_count';
    const ICICI_2FA_APPROVE_ROUTE_FAILURES_COUNT                    = 'icici_2fa_approve_route_failures_count';
    const PAYOUTS_BATCH_PAYOUT_ENTITY_CREATION_FAILED_WEBHOOK_FAILED = 'payouts_batch_payout_entity_creation_failed_webhook_failed';
    const CREDITS_REVERSE_FOR_LEDGER_PAYOUT_FOR_INSUFFICIENT_BALANCE_COUNT = 'credits_reverse_for_ledger_payout_for_insufficient_balance_count';
    const LEDGER_STATUS_CRON_FAILURE_COUNT                          = 'ledger_status_cron_failure_count';
    const PARTNER_BANK_ON_HOLD_FAILED                               = 'partner_bank_on_hold_failed';
    const PAYOUT_CREATE_SUBMITTED_PROCESS_JOB_ERROR_TOTAL           = 'payout_create_submitted_process_job_error_total';
    const SERVER_ERROR_PRICING_RULE_ABSENT_TOTAL                    = 'server_error_pricing_rule_absent_total';
    const PAYOUT_TO_CARDS_VAULT_TOKEN_DELETION_RETRIES_EXHAUSTED    = 'payout_to_cards_vault_token_deletion_retries_exhausted';
    const SUB_ACCOUNT_PAYOUT_TYPE_SET_TOTAL                         = 'sub_account_payout_type_set_total';
    const BULK_PAYOUTS_INTERNAL_SERVER_ERROR                        = 'bulk_payouts_internal_server_error';
    const BULK_PAYOUTS_DUPLICATE_IKEY                               = 'bulk_payouts_duplicate_ikey';
    const BULK_PAYOUTS_PROCESSING_BAD_REQUEST_ERROR                 = 'bulk_payouts_processing_bad_request_error';
    const RBL_VIRTUAL_ACCOUNT_BANKING_JOB_FAILURES_COUNT            = 'rbl_virtual_account_banking_job_failures_count';
    const RBL_VIRTUAL_ACCOUNT_BANKING_DISPATCH_FAILURE_COUNT        = 'rbl_virtual_account_banking_dispatch_failure_count';
    const RBL_VIRTUAL_ACCOUNT_BANKING_COMPLETED_DURATION_SECONDS    = 'rbl_virtual_account_banking_completed_duration_seconds.histogram';
    const FTS_MODE_FETCH_FAILURES_COUNT                             = 'fts_mode_fetch_failures_count';
    const FUND_MANAGEMENT_PAYOUT_CREATION_DISPATCH_FAILURE_COUNT    = 'fund_management_payout_creation_dispatch_failure_count';
    const LEDGER_LITE_BALANCE_FETCH_ERROR_COUNT                     = 'ledger_lite_balance_fetch_error_count';
    const FUND_MANAGEMENT_PAYOUT_CHECK_JOB_FAILURES_COUNT           = 'fund_management_payout_check_job_failures_count';
    const FUND_MANAGEMENT_PAYOUT_INITIATE_JOB_FAILURES_COUNT        = 'fund_management_payout_initiate_job_failures_count';

    const BULK_PAYOUT_ERROR_DESCRIPTION_COUNT                       = 'bulk_payout_error_description_count';

    const FMP_INITIATE_DISABLE_REDIS_FAILURES_COUNT                 = 'fmp_initiate_disable_redis_failures_count';
    const FUND_MANAGEMENT_PAYOUT_CRON_DISPATCH_FAILURES_COUNT       = 'fund_management_payout_cron_dispatch_failures_count';
    const FMP_LESS_THAN_FIFTY_PERCENT_LITE_BALANCE_COUNT            = 'fmp_less_than_fifty_percent_lite_balance_count';
    const PAYOUTS_SMART_ROUTING_FAILURES_COUNT                      = 'payouts_smart_routing_failure_count';
    const FTS_SMART_ROUTING_FAILURES_COUNT                          = 'fts_smart_routing_failures_count';
    const SMART_ROUTING_BAS_FETCH_FAILURES_COUNT                    = 'smart_routing_bas_fetch_failures_count';
    const TOTAL_SMART_ROUTING_PAYOUTS_COUNT                         = 'total_smart_routing_payouts_count';
    const NEGATIVE_FREE_PAYOUT_CONSUMED_COUNT                       = 'negative_free_payout_consumed_count';


    // Payout Service Metrics/Alerts
    const INVALID_PAYOUT_CREATE_REQUEST_TO_PAYOUT_SERVICE = 'invalid_payout_create_request_to_payout_service';
    const PAYOUT_SERVICE_TIME_OUT_EXCEPTION               = "payout_service_time_out_exception";

    const PAYOUT_SERVICE_REQUEST_FAILED                   = "payout_service_request_failed";
    const SERVER_ERROR_PAYOUT_SERVICE_REQUEST_FAILED      = "server_error_payout_service_request_failed";

    const PAYOUT_SERVICE_WORKFLOW_ACTION_FAILED           = "PAYOUT_SERVICE_WORKFLOW_ACTION_FAILED";

    // Histograms
    const PAYOUT_QUEUED_TO_CREATED_DURATION_SECONDS                      = 'payout_queued_to_created_duration_seconds.histogram';
    const PAYOUT_QUEUED_TO_CANCELLED_DURATION_SECONDS                    = 'payout_queued_to_cancelled_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_REJECTED_DURATION_SECONDS                    = 'payout_pending_to_rejected_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_QUEUED_DURATION_SECONDS                      = 'payout_pending_to_queued_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_CREATED_DURATION_SECONDS                     = 'payout_pending_to_created_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS                   = 'payout_created_to_initiated_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_FAILED_DURATION_SECONDS                      = 'payout_created_to_failed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS                 = 'payout_initiated_to_processed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS                  = 'payout_initiated_to_reversed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_FAILED_DURATION_SECONDS                    = 'payout_initiated_to_failed_duration_seconds.histogram';
    const PAYOUT_PROCESSED_TO_REVERSED_DURATION_SECONDS                  = 'payout_processed_to_reversed_duration_seconds.histogram';
    const PAYOUT_BATCH_SUBMITTED_TO_CREATED_DURATION_SECONDS             = 'payout_batch_submitted_to_created_duration_seconds.histogram';
    const PAYOUT_BATCH_SUBMITTED_TO_FAILED_DURATION_SECONDS              = 'payout_batch_submitted_to_failed_duration_seconds.histogram';
    const PAYOUT_BATCH_SUBMITTED_TO_ON_HOLD_DURATION_SECONDS             = 'payout_batch_submitted_to_on_hold_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_CREATED_DURATION_SECONDS                   = 'payout_scheduled_to_created_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_CANCELLED_DURATION_SECONDS                 = 'payout_scheduled_to_cancelled_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_FAILED_DURATION_SECONDS                    = 'payout_scheduled_to_failed_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_REJECTED_DURATION_SECONDS                  = 'payout_scheduled_to_rejected_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_BATCH_SUBMITTED_DURATION_SECONDS           = 'payout_scheduled_to_batch_submitted_duration_seconds.histogram';
    const PAYOUT_SCHEDULED_TO_ON_HOLD_DURATION_SECONDS                   = 'payout_scheduled_to_on_hold_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_SCHEDULED_DURATION_SECONDS                   = 'payout_pending_to_scheduled_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_BATCH_SUBMITTED_DURATION_SECONDS             = 'payout_pending_to_batch_submitted_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_ON_HOLD_DURATION_SECONDS                     = 'payout_pending_to_on_hold_duration_seconds.histogram';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_CREATED_DURATION_SECONDS    = 'payout_create_request_submitted_to_created_duration_seconds.histogram';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_FAILED_DURATION_SECONDS     = 'payout_create_request_submitted_to_failed_duration_seconds.histogram';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_ON_HOLD_DURATION_SECONDS    = 'payout_create_request_submitted_to_on_hold_duration_seconds.histogram';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_QUEUED_DURATION_SECONDS     = 'payout_create_request_submitted_to_queued_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_CREATED_DURATION_SECONDS                     = 'payout_on_hold_to_created_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_FAILED_DURATION_SECONDS                      = 'payout_on_hold_to_failed_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_QUEUED_DURATION_SECONDS                      = 'payout_on_hold_to_queued_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_CANCELLED_DURATION_SECONDS                   = 'payout_on_hold_to_cancelled_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_QUEUED_DURATION_SECONDS                      = 'payout_created_to_queued_duration_seconds.histogram';
    const FUND_MANAGEMENT_PAYOUT_CHECK_COMPLETED_DURATION_SECONDS        = 'fund_management_payout_check_completed_duration_seconds.histogram';
    const FUND_MANAGEMENT_PAYOUT_INITIATED_COMPLETED_DURATION_SECONDS    = 'fund_management_payout_initiated_completed_duration_seconds.histogram';
    const PAYOUTS_SMART_ROUTING_COMPLETED_DURATION_MS                    = 'payouts_smart_routing_completed_duration_ms.histogram';

    // payout create route
    const COMPOSITE_PAYOUT_CONTACT_FUND_ACCOUNT_CREATE_DURATION          = 'composite_payout_contact_fund_account_create_duration';
    const FREE_PAYOUT_CHECK_DURATION                                     = 'free_payout_check_duration';
    const PAYOUT_ENTITY_CREATE_AND_PROCESS_DURATION                      = 'payout_entity_create_and_process_duration';
    const PAYOUT_LEDGER_PROCESS_DURATION                                 = 'payout_ledger_process_duration';
    const PAYOUT_FTS_SYNC_CALL_DURATION                                  = 'payout_fts_sync_call_duration';
    const SET_PRICING_RULE_INFO_IN_PAYOUT_SERVICE_REDIS_DURATION         = 'set_pricing_rule_info_in_payout_service_redis_duration';

    // Dimension constants
    const SOURCE         = 'source';
    const BATCH          = 'batch';
    const API            = 'api';
    const DASHBOARD      = 'dashboard';
    const IS_BANKING     = 'is_banking';
    const IS_JOB_DELETED = 'is_job_deleted';
    const ACCOUNT_TYPE   = 'account_type';

    const ERROR_DESCRIPTION     = 'error_description';
    const FAILURE_STATUS_REASON = 'failure_status_reason';
    const IS_FIRST_TERMINAL     = 'is_first_terminal';
    const WITHIN_SLA            = 'within_sla';

    // 3600 seconds
    const PAYOUT_FAILURE_SLA = 7200;

    // FUND loading metric
    const FUND_LOADING_VA_CALLBACK = 'fund_loading_va_callback';
    const FUND_LOADING_VA_CALLBACK_FAILURE = 'fund_loading_va_callback_failure';


    public static function pushStatusChangeMetrics(Entity $payout, string $previousStatus = null)
    {
        $currentStatus = $payout->getStatus();

        try
        {
            if (self::getIsTestPayout($payout) === true)
            {
                return;
            }

            if ($payout->getStatusCode() === ErrorCode::BAD_REQUEST_SUSPICIOUS_TRANSACTION)
            {
                return;
            }

            // adding this if clause for ledger based payouts
            // Whenever a payout is initiated thru the ledger microservice, the payout moves to the created state
            // before the balance checks. Thus, if this payout has to be queued for low balance, the payout moves
            // from created to queued state. For non-ledger service payouts, that's illegal.
            // But for ledger case, it is legal.
            // TODO: Resolve this in a cleaner way when payout states go thru the simplification changes.
            if ((Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) and
                $previousStatus === Status::CREATED and
                $currentStatus === Status::QUEUED) === false)
            {
                Status::validateStatusUpdate($currentStatus, $previousStatus);
            }

            if (empty($previousStatus) === false)
            {
                $functionName = self::getFunctionNameToCallForStatusChange($currentStatus, $previousStatus);

                self::$functionName($payout);
            }

            self::pushCountMetrics($payout);
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYOUT_METRIC_PUSH_EXCEPTION,
                [
                    'id'              => $payout->getPublicId(),
                    'previous_status' => $previousStatus,
                    'current_status'  => $currentStatus,
                ]);

            app('trace')->count(self::PAYOUT_METRIC_PUSH_EXCEPTION_COUNT,
                                [
                                    'previous_status' => $previousStatus,
                                    'current_status'  => $currentStatus,
                                    'environment'     => app('env'),
                                    'mode'            => app('request.ctx')->getMode() ?: 'none'
                                ]);
        }
    }

    protected static function getFunctionNameToCallForStatusChange(string $currentStatus, string $previousStatus)
    {
        $functionName = 'push' . ucfirst($previousStatus) . 'To' . ucfirst($currentStatus) . 'Metrics';

        return camel_case($functionName);
    }

    protected static function pushCountMetrics(Entity $payout)
    {
        $currentStatus = $payout->getStatus();

        $metricConstantKey = 'PAYOUT_' . strtoupper($currentStatus) . '_TOTAL';

        $metricConstantValue = constant("self::{$metricConstantKey}");

        $extraDimensions = self::getMetricExtraDimensions($payout);

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);

        app('trace')->count($metricConstantValue, $metricDimensions);
    }

    protected static function pushQueuedToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getQueuedAt();

        app('trace')->histogram(
            self::PAYOUT_QUEUED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreatedToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getCreatedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATED_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushQueuedToCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCancelledAt() - $payout->getQueuedAt();

        app('trace')->histogram(
            self::PAYOUT_QUEUED_TO_CANCELLED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getRejectedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_REJECTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushBatchSubmittedToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getInitiatedAt() - $payout->getBatchSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_BATCH_SUBMITTED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushBatchSubmittedToFailedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getFailedAt() - $payout->getBatchSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_BATCH_SUBMITTED_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreateRequestSubmittedToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getInitiatedAt() - $payout->getCreateRequestSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATE_REQUEST_SUBMITTED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreateRequestSubmittedToFailedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getFailedAt() - $payout->getCreateRequestSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATE_REQUEST_SUBMITTED_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreateRequestSubmittedToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getCreateRequestSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATE_REQUEST_SUBMITTED_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    /**
     * This represents the duration between payout.initiate and fta.initiate
     * @param Entity $payout
     */
    protected static function pushCreatedToInitiatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        //
        // Since we are not storing the timestamp when the fta was initiated,
        // therefore we use the current timestamp
        // payout.created_at is the time of payout entity creation
        // payout.initiated_at is the time when the payout is is move to created state
        // This is useful for queued payouts, where status moves from queued -> created
        //
        $timeDuration = Carbon::now()->getTimestamp() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreatedToFailedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);
        $timeDuration     = $payout->getFailedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATED_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushInitiatedToReversedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);
        $timeDuration     = $payout->getReversedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushInitiatedToFailedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions      = self::getMetricDimensions($payout, $extraDimensions);
        $initiatedToFailedTime = $payout->getFailedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_FAILED_DURATION_SECONDS,
            $initiatedToFailedTime,
            $metricDimensions);
    }

    protected static function pushInitiatedToProcessedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getProcessedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushProcessedToReversedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);
        $timeDuration     = $payout->getReversedAt() - $payout->getProcessedAt();

        app('trace')->histogram(
            self::PAYOUT_PROCESSED_TO_REVERSED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getInitiatedAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_SCHEDULED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToFailedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);
        $timeDuration     = $payout->getFailedAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_SCHEDULED_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCancelledAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_SCHEDULED_TO_CANCELLED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getRejectedAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_SCHEDULED_TO_REJECTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToBatchSubmittedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getBatchSubmittedAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_SCHEDULED_TO_BATCH_SUBMITTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToScheduledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getScheduledAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_SCHEDULED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToBatchSubmittedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getBatchSubmittedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_BATCH_SUBMITTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushOnHoldToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getOnHoldAt();

        app('trace')->histogram(
            self::PAYOUT_ON_HOLD_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushOnHoldToCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCancelledAt() - $payout->getOnHoldAt();

        app('trace')->histogram(
            self::PAYOUT_ON_HOLD_TO_CANCELLED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushOnHoldToFailedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getFailedAt() - $payout->getOnHoldAt();

        app('trace')->histogram(
            self::PAYOUT_ON_HOLD_TO_FAILED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushOnHoldToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getOnHoldAt();

        app('trace')->histogram(
            self::PAYOUT_ON_HOLD_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushScheduledToOnHoldMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getOnHoldAt() - $payout->getScheduledAt();

        app('trace')->histogram(
            self::PAYOUT_ON_HOLD_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushBatchSubmittedToOnHoldMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getOnHoldAt() - $payout->getBatchSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_BATCH_SUBMITTED_TO_ON_HOLD_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreateRequestSubmittedToOnHoldMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getOnHoldAt() - $payout->getCreateRequestSubmittedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATE_REQUEST_SUBMITTED_TO_ON_HOLD_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToOnHoldMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getOnHoldAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_ON_HOLD_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToPendingOnOtpMetrics(Entity $payout)
    {
        // Nothing to do here as we don't track pending_on_otp timestamp currently.
        // This function is added to suppress the `PAYOUT_METRIC_PUSH_EXCEPTION`
        // exception that occurs without this.
    }

    protected static function pushPendingOnOtpToCreatedMetrics(Entity $payout)
    {
        // Nothing to do here as we don't track pending_on_otp timestamp currently.
        // This function is added to suppress the `PAYOUT_METRIC_PUSH_EXCEPTION`
        // exception that occurs without this.
    }

    protected static function pushPendingOnOtptoFailedMetrics(Entity $payout)
    {
        // Nothing to do here as we don't track pending_on_otp timestamp currently.
        // This function is added to suppress the `PAYOUT_METRIC_PUSH_EXCEPTION`
        // exception that occurs without this.
    }

    protected static function getMetricDimensions(Entity $payout, array $extra = []): array
    {
        $dimensions = $extra + [
                Entity::MODE          => $payout->getMode(),
                Entity::METHOD        => $payout->getMethod(),
                Entity::CHANNEL       => $payout->getChannel(),
                Balance::ACCOUNT_TYPE => $payout->balance->getAccountType(),
                self::SOURCE          => self::getSource($payout),
                self::IS_BANKING      => $payout->balance->isTypeBanking(),
            ];

        return $dimensions;
    }

    protected static function getMetricExtraDimensions(Entity $payout)
    {
        $currentStatus = $payout->getStatus();

        switch ($currentStatus)
        {
            case Status::REVERSED:
            case Status::FAILED:

                return [
                    Entity::FAILURE_REASON      => $payout->getFailureReason(),
                    self::FAILURE_STATUS_REASON => self::getFailureStatusReason($payout),
                    self::IS_FIRST_TERMINAL     => self::getIsFirstTerminal($payout, $currentStatus),
                    self::WITHIN_SLA            => self::getWithinSla($payout),
                ];

            case Status::PROCESSED:

                return [
                    self::IS_FIRST_TERMINAL => self::getIsFirstTerminal($payout, $currentStatus),
                    self::WITHIN_SLA        => self::getWithinSla($payout),
                ];

            default:
                return [];
        }
    }

    protected static function getFailureStatusReason(Entity $payout)
    {
        $payoutError = new PayoutError($payout);

        $errorDetails = $payoutError->getErrorDetails();

        return $errorDetails['reason'] ?? null ;
    }

    protected static function getWithinSla(Entity $payout)
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if (($payout->getInitiatedAt() !== null) and
            (($currentTime - $payout->getInitiatedAt()) > self::PAYOUT_FAILURE_SLA))
        {
            return false;
        }

        return true;
    }

    protected static function getIsTestPayout(Entity $payout)
    {
        $testMerchantIds = (new AdminService)->getConfigKey(
            [
                'key' => ConfigKey::RX_PAYOUT_TEST_MERCHANTS_TO_EXCLUDE_FOR_METRICS
            ]);

        if (in_array($payout->getMerchantId(), $testMerchantIds, true) === true)
        {
            return true;
        }

        return false;
    }

    // getIsFirstTerminal returns true only if the payout is marked as failed or processed or reversed for the first time
    // for the status reversed,
    //      if the payout is actually getting reversed, we return true
    //      if a already failed payout is moving reversed, we return false
    // for the status processed,
    //      if the payout is actually getting processed, we return true
    //      while reversing the payout, for the sake of proper status transitions if we are marking the payout as processed, we return false
    // for the status failed,
    //      a payout can never go to processed or reversed from failed in the same session, so we always return true
    protected static function getIsFirstTerminal(Entity $payout, string $status)
    {
        switch ($status)
        {
            case Status::REVERSED:
                if ($payout->getFailedAt() !== null)
                {
                    return false;
                }
                break;

            case Status::PROCESSED:
                if (($payout->getReversalStatusUpdateIntentForMetrics() === true) or
                    ($payout->getFailedAt() !== null))
                {
                    return false;
                }
                break;
        }

        return true;
    }

    protected static function getSource(Entity $payout)
    {
        if ($payout->hasBatch() === true)
        {
            return self::BATCH;
        }

        if (empty($payout->getUserId()) === false)
        {
            return self::DASHBOARD;
        }

        return self::API;
    }
}
