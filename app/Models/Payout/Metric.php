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
    const SOURCE    = 'source';
    const BATCH     = 'batch';
    const API       = 'api';
    const DASHBOARD = 'dashboard';

    public static function pushPayoutStatusChangeMetrics(Entity $payout, $previousStatus)
    {
        $currentStatus = $payout->getStatus();

        try {
            Status::validatePreviousToCurrentMapping($previousStatus, $currentStatus);

            $functionName = self::getFunctionNameToCall($previousStatus, $currentStatus);

            self::$functionName($payout);
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYOUT_METRIC_PUSH_EXCEPTION,
                [
                    'oldStatus' => $previousStatus,
                    'newStatus' => $currentStatus,
                ]);
        }
    }

    protected static function getFunctionNameToCall($previousStatus, $currentStatus)
    {
        $functionName = 'push';

        if ($previousStatus !== null)
        {
            $functionName .= ucfirst($previousStatus) . 'To';
        }

        $functionName .= ucfirst($currentStatus) . 'Metrics';

        return $functionName;
    }

    protected static function pushCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_CREATED_TOTAL, $metricDimensions);
    }

    protected static function pushPendingMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_PENDING_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_QUEUED_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getQueuedAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_QUEUED_TO_CREATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushQueuedToCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCancelledAt() - $payout->getQueuedAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_QUEUED_TO_CANCELLED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getRejectedAt() - $payout->getPendingAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_PENDING_TO_REJECTED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getQueuedAt() - $payout->getPendingAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_PENDING_TO_QUEUED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushPendingToCreatedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getCreatedAt() - $payout->getPendingAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_PENDING_TO_CREATED_DURATION_SECONDS,
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
        $timeDuration     = Carbon::now()->getTimestamp() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS,
            $timeDuration,
            $metricDimensions);
    }

    protected static function pushCreatedToFailedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);
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
        $metricDimensions = self::getMetricDimensions($payout);
        $timeDuration     = $payout->getReversedAt() - $payout->getProcessedAt();

        app('trace')->histogram(
            self::PAYOUT_PROCESSED_TO_REVERSED_DURATION_SECONDS,
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
