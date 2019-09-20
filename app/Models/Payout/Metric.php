<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Merchant\Balance\Entity as Balance;

final class Metric
{
    // Counters
    const PAYOUT_CREATED_TOTAL   = 'payout_created_total';
    const PAYOUT_PENDING_TOTAL   = 'payout_pending_total';
    const PAYOUT_REJECTED_TOTAL  = 'payout_rejected_total';
    const PAYOUT_QUEUED_TOTAL    = 'payout_queued_total';
    const PAYOUT_CANCELLED_TOTAL = 'payout_cancelled_total';

    // Histograms
    const PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS       = 'payout_created_to_initiated_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS     = 'payout_initiated_to_processed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS      = 'payout_initiated_to_reversed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_FAILED_DURATION_SECONDS        = 'payout_initiated_to_failed_duration_seconds.histogram';
    const PAYOUT_INITIATED_TO_FTA_INITIATED_DURATION_SECONDS = 'payout_initiated_to_fta_initiated_duration_seconds.histogram';

    // Dimension constants
    const IS_BULK_PAYOUT = 'is_bulk_payout';

    public static function getMetricDimensions(Entity $payout, array $extra = []): array
    {
        $dimensions = $extra + [
                Entity::MODE          => $payout->getMode(),
                Entity::METHOD        => $payout->getMethod(),
                Entity::CHANNEL       => $payout->getChannel(),
                Balance::ACCOUNT_TYPE => $payout->balance->getAccountType(),
                self::IS_BULK_PAYOUT  => ($payout->getBatchId() !== null ? true : false),
            ];

        return $dimensions;
    }

    public static function pushPayoutStatusChangeMetrics(Trace $trace, Entity $payout, string $status)
    {
        switch ($status)
        {
            case Status::PENDING:
                self::pushPendingMetrics($trace, $payout);
                break;

            case Status::REJECTED:
                self::pushRejectedMetrics($trace, $payout);
                break;

            case Status::QUEUED:
                self::pushQueuedMetrics($trace, $payout);
                break;

            case Status::CANCELLED:
                self::pushCancelledMetrics($trace, $payout);
                break;

            case Status::INITIATED:
                self::pushFtaInitiatedMetrics($trace, $payout);
                break;

            case Status::CREATED:
                self::pushPayoutInitiatedMetrics($trace, $payout);
                break;

            case Status::PROCESSED:
                self::pushProcessedMetrics($trace, $payout);
                break;

            case Status::REVERSED:
                self::pushReversedMetrics($trace, $payout);
                break;

            case Status::FAILED:
                self::pushFailedMetrics($trace, $payout);
                break;

            default:
                return;
        }
    }

    public static function pushPayoutCreateMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        $trace->count(self::PAYOUT_CREATED_TOTAL, $metricDimensions);
    }

    protected static function pushPendingMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        $trace->count(self::PAYOUT_PENDING_TOTAL, $metricDimensions);
    }

    protected static function pushRejectedMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        $trace->count(self::PAYOUT_REJECTED_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        $trace->count(self::PAYOUT_QUEUED_TOTAL, $metricDimensions);
    }

    protected static function pushCancelledMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        $trace->count(self::PAYOUT_CANCELLED_TOTAL, $metricDimensions);
    }

    protected static function pushFtaInitiatedMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions            = self::getMetricDimensions($payout);
        $initiatedToFtaInitiatedTime = Carbon::now()->getTimestamp() - $payout->getInitiatedAt();

        $trace->histogram(
            self::PAYOUT_INITIATED_TO_FTA_INITIATED_DURATION_SECONDS,
            $initiatedToFtaInitiatedTime,
            $metricDimensions);
    }

    public static function pushPayoutInitiatedMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions       = self::getMetricDimensions($payout);
        $createdToInitiatedTime = $payout->getInitiatedAt() - $payout->getCreatedAt();

        $trace->histogram(
            self::PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS,
            $createdToInitiatedTime,
            $metricDimensions);
    }

    protected static function pushProcessedMetrics(Trace $trace, Entity $payout)
    {
        $metricDimensions         = self::getMetricDimensions($payout);
        $initiatedToProcessedTime = $payout->getProcessedAt() - $payout->getInitiatedAt();

        $trace->histogram(
            self::PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS,
            $initiatedToProcessedTime,
            $metricDimensions);
    }

    protected static function pushReversedMetrics(Trace $trace, Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions        = self::getMetricDimensions($payout, $extraDimensions);
        $initiatedToReversedTime = $payout->getReversedAt() - $payout->getInitiatedAt();

        $trace->histogram(
            self::PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS,
            $initiatedToReversedTime,
            $metricDimensions);
    }

    protected static function pushFailedMetrics(Trace $trace, Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions      = self::getMetricDimensions($payout, $extraDimensions);
        $initiatedToFailedTime = $payout->getFailedAt() - $payout->getInitiatedAt();

        $trace->histogram(
            self::PAYOUT_INITIATED_TO_FAILED_DURATION_SECONDS,
            $initiatedToFailedTime,
            $metricDimensions);
    }
}
