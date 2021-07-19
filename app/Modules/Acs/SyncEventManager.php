<?php

namespace RZP\Modules\Acs;

use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Trace\TraceCode;
use Razorpay\Outbox\Job\Core as Outbox;
use RZP\Models\Merchant\Repository as MerchantRepo;

/**
 * Class SyncEventManager
 *
 * Added as singleton to app container.
 * Collects account IDs to be synced to account service and
 * de-dupes them before triggering sync events to avoid
 * generating un-necessary load by removing redundant syncing.
 *
 * @package RZP\Services\Acs
 */
class SyncEventManager
{
    const SINGLETON_NAME = 'acs.syncManager';
    const OUTBOX_JOB_NAME = 'acs.sync_account.v1';

    /** @var Application $app */
    public $app;

    /** @var Trace $trace */
    public $trace;

    /** @var Outbox $outbox */
    public $outbox;

    // accountIds are stored as [id => id] instead of plain arrays to behave as sets
    protected $liveAccountIds = [];
    protected $testAccountIds = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->trace = $this->app['trace'];
        $this->outbox = $this->app['outbox'];
    }

    public function __destruct()
    {
        // If there are some unreported sync events, log them.
        // Do not throw exception, it would result in unclean/fatal shutdown.
        if ($this->hasUnreportedAccountIds())
        {
            if ($this->hasUnreportedLiveAccountIds())
            {
                $this->trace->count(
                    Metric::ACS_SYNC_ALERT_UNREPORTED_ACCOUNTS,
                    [Metric::LABEL_RZP_MODE => Mode::LIVE],
                    count($this->liveAccountIds)
                );
            }

            if ($this->hasUnreportedTestAccountIds())
            {
                $this->trace->count(
                    Metric::ACS_SYNC_ALERT_UNREPORTED_ACCOUNTS,
                    [Metric::LABEL_RZP_MODE => Mode::TEST],
                    count($this->testAccountIds)
                );
            }

            $this->trace->critical(TraceCode::ACS_SYNC_UNREPORTED_ACCOUNTS, [
                'liveAccountIds' => array_values($this->liveAccountIds),
                'testAccountIds' => array_values($this->testAccountIds),
                'metadata' => $this->getContext($this->app),
            ]);
        }
    }

    public function getLiveAccountIds(): array
    {
        return $this->liveAccountIds;
    }

    public function getTestAccountIds(): array
    {
        return $this->testAccountIds;
    }

    public function hasUnreportedLiveAccountIds(): bool
    {
        return empty($this->liveAccountIds) === false;
    }

    public function hasUnreportedTestAccountIds(): bool
    {
        return empty($this->testAccountIds) === false;
    }

    public function hasUnreportedAccountIds(): bool
    {
        return $this->hasUnreportedLiveAccountIds()
            or $this->hasUnreportedTestAccountIds();
    }

    /**
     * Records account id to be published for sync
     *
     * @param string $accountId
     * @param string $mode
     *
     * @throws LogicException
     */
    public function recordAccountSync(string $accountId, $mode = Mode::LIVE)
    {
        switch ($mode) {
            case Mode::LIVE:
                $this->liveAccountIds[$accountId] = $accountId;
                break;
            case Mode::TEST:
                $this->testAccountIds[$accountId] = $accountId;
                break;
            default:
                $this->trace->count(
                    Metric::ACS_SYNC_ALERT_UNKNOWN_MODE,
                    [Metric::LABEL_RZP_MODE => $mode]
                );
                $this->trace->critical(TraceCode::ACS_SYNC_UNKNOWN_MODE, [
                    'accountId' => $accountId,
                    'mode' => $mode,
                ]);
        }
    }

    /**
     * Publishes sync events for account ids recorded to outbox.
     * Ideally this should be called only once after all the business logic has completed.
     */
    public function publishOutboxJobs(array $metadata)
    {
        foreach ($this->liveAccountIds as $accountId => $ignoredValue) {
            $this->publishOutboxJob($accountId, Mode::LIVE, $metadata);
        }

//        // Skipping publishing test accounts because we have decided to only sync live for now
//        // Enabling this would need to use test mode outbox instance
//        foreach ($this->testAccountIds as $accountId => $ignoredValue)
//        {
//            $this->publishOutboxJob($accountId, Mode::TEST, $metadata);
//        }

        $this->liveAccountIds = [];
        $this->testAccountIds = [];
    }

    public function publishOutboxJob(string $accountId, string $mode, array $metadata)
    {
        // if account service sync is not enabled, do not publish outbox jobs
        if ($this->app['config']->get('applications.acs.sync_enabled') === false)
        {
            return;
        }

        $metricDimensions = array_merge([Metric::LABEL_RZP_MODE => $mode], $metadata);
        try {
            // TODO: verify and update as per sync request proto
            $jobPayload = [
                'account_id' => $accountId,
                'mode' => $mode,
                'mock' => false,
                'metadata' => $metadata,
            ];

            // this needs to be in a transaction due to a hard check in outbox implementation
            (new MerchantRepo())->connection($mode)->transaction(function () use ($mode, $jobPayload) {
                $this->outbox->send(self::OUTBOX_JOB_NAME, $jobPayload, $mode, false);
            });

            $this->trace->info(TraceCode::ACS_SYNC_EVENT_PUBLISHED, $jobPayload);
            $this->trace->count(Metric::ACS_SYNC_EVENT_PUBLISHED, $metricDimensions);
        } catch (\Throwable $e) {
            // Just logging and ignoring exception here to not mess with request flow
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ACS_SYNC_EVENT_PUBLISH_FAILED, $jobPayload);
            $this->trace->count(Metric::ACS_SYNC_ALERT_EVENT_PUBLISH_FAILED, $metricDimensions);
        }
    }

    /**
     * Context for sync event to help with debugging
     * This includes route/job names or other relevant data points.
     */
    protected function getContext(Application $app): array
    {
        $context = [];
        if (isset($app['request.ctx']) and empty($app['request.ctx']) === false)
        {
            $requestContext = $app['request.ctx'];
            $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] = $requestContext->getInternalAppName();
            $context[Metric::LABEL_ROUTE] = $requestContext->getRoute();
        }

        if (isset($app['worker.ctx']) and empty($app['worker.ctx']) === false)
        {
            $workerContext = $app['worker.ctx'];
            $jobName = get_class($app['worker.ctx']);
            if (method_exists($workerContext, 'getJobName'))
            {
                $jobName = $workerContext->getJobName();
            }
            $jobName = str_replace('\\', '_', $jobName);
            $context[Metric::LABEL_ASYNC_JOB_NAME] = $jobName;
        }

        $context[Metric::LABEL_ROUTE] =
            $context[Metric::LABEL_ROUTE] ?? Metric::LABEL_NONE_VALUE;
        $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] =
            $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] ?? Metric::LABEL_NONE_VALUE;
        $context[Metric::LABEL_ASYNC_JOB_NAME] =
            $context[Metric::LABEL_ASYNC_JOB_NAME] ?? Metric::LABEL_NONE_VALUE;

        return $context;
    }

}
