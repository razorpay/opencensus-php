<?php

namespace RZP\Models\Payout;

use App;
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
    const SOURCE    = 'source';
    const BATCH     = 'batch';
    const API       = 'api';
    const DASHBOARD = 'dashboard';

    public static function pushPayoutStatusChangeMetrics(Entity $payout)
    {
        $status = $payout->getStatus();

        switch ($status)
        {
            case Status::PENDING:
                self::pushPendingMetrics($payout);
                break;

            case Status::REJECTED:
                self::pushRejectedMetrics($payout);
                break;

            case Status::QUEUED:
                self::pushQueuedMetrics($payout);
                break;

            case Status::CANCELLED:
                self::pushCancelledMetrics($payout);
                break;

            case Status::INITIATED:
                self::pushFtaInitiatedMetrics($payout);
                break;

            case Status::CREATED:
                self::pushPayoutInitiatedMetrics($payout);
                break;

            case Status::PROCESSED:
                self::pushProcessedMetrics($payout);
                break;

            case Status::REVERSED:
                self::pushReversedMetrics($payout);
                break;

            case Status::FAILED:
                self::pushFailedMetrics($payout);
                break;

            default:
                return;
        }
    }

    public static function pushPayoutCreateMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_CREATED_TOTAL, $metricDimensions);
    }

    protected static function pushPendingMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_PENDING_TOTAL, $metricDimensions);
    }

    protected static function pushRejectedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_REJECTED_TOTAL, $metricDimensions);
    }

    protected static function pushQueuedMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_QUEUED_TOTAL, $metricDimensions);
    }

    protected static function pushCancelledMetrics(Entity $payout)
    {
        $metricDimensions = self::getMetricDimensions($payout);

        app('trace')->count(self::PAYOUT_CANCELLED_TOTAL, $metricDimensions);
    }

    protected static function pushFtaInitiatedMetrics(Entity $payout)
    {
        $metricDimensions            = self::getMetricDimensions($payout);
        $initiatedToFtaInitiatedTime = Carbon::now()->getTimestamp() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_FTA_INITIATED_DURATION_SECONDS,
            $initiatedToFtaInitiatedTime,
            $metricDimensions);
    }

    public static function pushPayoutInitiatedMetrics(Entity $payout)
    {
        $metricDimensions       = self::getMetricDimensions($payout);
        $createdToInitiatedTime = $payout->getInitiatedAt() - $payout->getCreatedAt();

        app('trace')->getTrace()->histogram(
            self::PAYOUT_CREATED_TO_INITIATED_DURATION_SECONDS,
            $createdToInitiatedTime,
            $metricDimensions);
    }

    protected static function pushProcessedMetrics(Entity $payout)
    {
        $metricDimensions         = self::getMetricDimensions($payout);
        $initiatedToProcessedTime = $payout->getProcessedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_PROCESSED_DURATION_SECONDS,
            $initiatedToProcessedTime,
            $metricDimensions);
    }

    protected static function pushReversedMetrics(Entity $payout)
    {
        $extraDimensions = [
            Entity::FAILURE_REASON => $payout->getFailureReason(),
        ];

        $metricDimensions        = self::getMetricDimensions($payout, $extraDimensions);
        $initiatedToReversedTime = $payout->getReversedAt() - $payout->getInitiatedAt();

        app('trace')->histogram(
            self::PAYOUT_INITIATED_TO_REVERSED_DURATION_SECONDS,
            $initiatedToReversedTime,
            $metricDimensions);
    }

    protected static function pushFailedMetrics(Entity $payout)
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
        if (empty($payout->getBatchId()) === false)
        {
            return self::BATCH;
        }

        $dashboardUser = app('basicauth')->getUser();

        if (empty($dashboardUser) === false)
        {
            return self::DASHBOARD;
        }

        return self::API;
    }
}
