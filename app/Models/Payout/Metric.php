<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Balance\Entity as Balance;

final class Metric
{
    // Counters
    const PAYOUT_CREATED_TOTAL                              = 'payout_created_total';
    const PAYOUT_PENDING_TOTAL                              = 'payout_pending_total';
    const PAYOUT_QUEUED_TOTAL                               = 'payout_queued_total';
    const PAYOUT_FAILED_TOTAL                               = 'payout_failed_total';
    const PAYOUT_REVERSED_TOTAL                             = 'payout_reversed_total';
    const PAYOUT_PROCESSED_TOTAL                            = 'payout_processed_total';
    const PAYOUT_INITIATED_TOTAL                            = 'payout_initiated_total';
    const PAYOUT_REJECTED_TOTAL                             = 'payout_rejected_total';
    const PAYOUT_CANCELLED_TOTAL                            = 'payout_cancelled_total';
    const PAYOUT_BATCH_SUBMITTED_TOTAL                      = 'payout_batch_submitted_total';
    const PAYOUT_SCHEDULED_TOTAL                            = 'payout_scheduled_total';
    const PAYOUT_ON_HOLD_TOTAL                              = 'payout_on_hold_total';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TOTAL             = 'payout_create_request_submitted_total';
    const PAYOUT_WORKFLOW_CREATION_FAILED_TOTAL             = 'payout_workflow_creation_failed_total';
    const PAYOUT_WORKFLOW_ACTION_FAILED_TOTAL               = 'payout_workflow_action_failed_total';
    const PAYOUT_WORKFLOW_ACTION_DUPLICATE_REQUEST_TOTAL    = 'payout_workflow_action_duplicate_request_total';

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
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_ON_HOLD_DURATION_SECONDS     = 'payout_create_request_submitted_to_on_hold_duration_seconds.histogram';
    const PAYOUT_CREATE_REQUEST_SUBMITTED_TO_QUEUED_DURATION_SECONDS     = 'payout_create_request_submitted_to_queued_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_CREATED_DURATION_SECONDS                     = 'payout_on_hold_to_created_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_FAILED_DURATION_SECONDS                      = 'payout_on_hold_to_failed_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_QUEUED_DURATION_SECONDS                      = 'payout_on_hold_to_queued_duration_seconds.histogram';
    const PAYOUT_ON_HOLD_TO_CANCELLED_DURATION_SECONDS                   = 'payout_on_hold_to_cancelled_duration_seconds.histogram';


    // Dimension constants
    const SOURCE     = 'source';
    const BATCH      = 'batch';
    const API        = 'api';
    const DASHBOARD  = 'dashboard';
    const IS_BANKING = 'is_banking';

    public static function pushStatusChangeMetrics(Entity $payout, string $previousStatus = null)
    {
        $currentStatus = $payout->getStatus();

        try
        {
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
                    Entity::FAILURE_REASON => $payout->getFailureReason(),
                ];
            default:
                return [];
        }
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
