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
    const PAYOUT_CREATED_TOTAL   = 'payout_created_total';
    const PAYOUT_PENDING_TOTAL   = 'payout_pending_total';
    const PAYOUT_QUEUED_TOTAL    = 'payout_queued_total';
    const PAYOUT_FAILED_TOTAL    = 'payout_failed_total';
    const PAYOUT_REVERSED_TOTAL  = 'payout_reversed_total';
    const PAYOUT_PROCESSED_TOTAL = 'payout_processed_total';
    const PAYOUT_INITIATED_TOTAL = 'payout_initiated_total';
    const PAYOUT_REJECTED_TOTAL  = 'payout_rejected_total';
    const PAYOUT_CANCELLED_TOTAL = 'payout_cancelled_total';

    // Histograms
    const PAYOUT_QUEUED_TO_CREATED_DURATION_SECONDS      = 'payout_queued_to_created_duration_seconds.histogram';
    const PAYOUT_QUEUED_TO_CANCELLED_DURATION_SECONDS    = 'payout_queued_to_cancelled_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_REJECTED_DURATION_SECONDS    = 'payout_pending_to_rejected_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_QUEUED_DURATION_SECONDS      = 'payout_pending_to_queued_duration_seconds.histogram';
    const PAYOUT_PENDING_TO_CREATED_DURATION_SECONDS     = 'payout_pending_to_created_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS   = 'payout_created_to_initiated_duration_seconds.histogram';
    const PAYOUT_CREATED_TO_FAILED_DURATION_SECONDS      = 'payout_created_to_failed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS = 'payout_initiated_to_processed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS  = 'payout_initiated_to_reversed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_FAILED_DURATION_SECONDS    = 'payout_initiated_to_failed_duration_seconds.histogram';
    const PAYOUT_PROCESSED_TO_REVERSED_DURATION_SECONDS  = 'payout_processed_to_reversed_duration_seconds.histogram';

    // Dimension constants
    const SOURCE     = 'source';
    const BATCH      = 'batch';
    const API        = 'api';
    const DASHBOARD  = 'dashboard';
    const IS_BANKING = 'is_banking';

    /**
     * List of status which have a count metric associated with them
     * @var array
     */
    protected static $statusListForCountMetrics = [
        Status::QUEUED,
        Status::PENDING,
        Status::REJECTED,
        Status::CANCELLED,
        Status::CREATED,
        Status::INITIATED,
        Status::FAILED,
        Status::REVERSED,
        Status::PROCESSED,
    ];

    protected static $internalStatusChangeFunctionName = [
        Status::CREATED => [
            Status::QUEUED  => 'pushQueuedMetrics'
        ]
    ];

    public static function pushStatusChangeMetrics(Entity $payout, string $previousStatus = null)
    {
        $currentStatus = $payout->getStatus();

        try
        {
            $isInternalChange = Status::isInternalStatusUpdate($currentStatus, $previousStatus);

            if ($isInternalChange === false)
            {
                Status::validateStatusUpdate($currentStatus, $previousStatus);
            }

            $functionName = self::getFunctionNameToCall($currentStatus, $previousStatus, $isInternalChange);

            self::$functionName($payout);
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

    protected static function getFunctionNameToCall(
        string $currentStatus,
        string $previousStatus = null,
        bool $isInternalChange = false)
    {
        if ($isInternalChange === true)
        {
            return self::$internalStatusChangeFunctionName[$previousStatus][$currentStatus];
        }

        $functionName = 'push';

        if ($previousStatus !== null)
        {
            $functionName .= ucfirst($previousStatus) . 'To';
        }

        $functionName .= ucfirst($currentStatus) . 'Metrics';

        return $functionName;
    }

    protected static function pushCountMetrics(Entity $payout)
    {
        $currentStatus = $payout->getStatus();

        if (in_array($currentStatus, self::$statusListForCountMetrics, true) === false)
        {
            return;
        }

        $functionName = 'push' . ucfirst($currentStatus) . 'Metrics';

        self::$functionName($payout);
    }

    protected static function pushCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_CREATED_TOTAL, $metricDimensions);
    }

    protected static function pushInitiatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_INITIATED_TOTAL, $metricDimensions);
    }

    protected static function pushRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_REJECTED_TOTAL, $metricDimensions);
    }

    protected static function pushCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_CANCELLED_TOTAL, $metricDimensions);
    }

    protected static function pushPendingMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_PENDING_TOTAL, $metricDimensions);
    }

    protected static function pushProcessedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_PROCESSED_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_QUEUED_TOTAL, $metricDimensions);
    }

    protected static function pushFailedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);

        app('trace')->count(self::PAYOUT_FAILED_TOTAL, $metricDimensions);
    }

    protected static function pushReversedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions = self::getMetricDimensions($payout, $extraDimensions);

        app('trace')->count(self::PAYOUT_REVERSED_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getQueuedAt();

        app('trace')->histogram(
            self::PAYOUT_QUEUED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
    }

    protected static function pushQueuedToCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCancelledAt() - $payout->getQueuedAt();

        app('trace')->histogram(
            self::PAYOUT_QUEUED_TO_CANCELLED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
    }

    protected static function pushPendingToRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getRejectedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_REJECTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
    }

    protected static function pushPendingToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
    }

    protected static function pushPendingToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getPendingAt();

        app('trace')->histogram(
            self::PAYOUT_PENDING_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
    }

    /**
     * This represents the duration between payout.initiate and fta.initiate
     * @param Entity $payout
     */
    protected static function pushCreatedToInitiatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        // Since we are not storing the timestamp when the fta was initiated,
        // therefore we use the current timestamp
        // payout.created_at is the time of payout entity creation
        // payout.initiated_at is the time when the payout is is move to created state
        // This is useful for queued payouts, where status moves from queued -> created
        $timeDuration     = Carbon::now()->getTimestamp() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
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

        self::pushCountMetrics($payout);
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

        self::pushCountMetrics($payout);
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

        self::pushCountMetrics($payout);
    }

    protected static function pushInitiatedToProcessedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getProcessedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);

        self::pushCountMetrics($payout);
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

        self::pushCountMetrics($payout);
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
