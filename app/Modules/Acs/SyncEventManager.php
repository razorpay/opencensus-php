<?php

namespace RZP\Modules\Acs;

use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use Razorpay\Outbox\Job\Core as Outbox;

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

    protected $stats = ['total' => ['count' => 0]];

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
     * @param PublicEntity $entity
     *
     */
    public function recordAccountSync(PublicEntity $entity)
    {
        $accountId = $entity->getMerchantId();
        $mode = $entity->getConnectionName();

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

        // for live mode, store the stats like the number of updates for each entity etc..
        if ($mode == Mode::LIVE)
        {
            // we log before the stats are updated as we want to see the number of updates
            // already applied in the request flow before the current update
            $logData = $this->getLogData($entity);
            $this->logEntityUpdate($logData);

            $entityName = $entity->getEntityName();
            if (array_key_exists($entityName, $this->stats) === false)
            {
                $this->stats[$entityName] = ['count' => 0];
            }
            $this->stats[$entityName]['count']++;
            $this->stats['total']['count']++;
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

        $this->resetAccountParams();
    }

    public function resetAccountParams()
    {
        $this->liveAccountIds = [];
        $this->testAccountIds = [];

        $this->stats = ['total' => ['count' => 0]];
    }

    public function publishOutboxJob(string $accountId, string $mode, array $metadata)
    {
        // if account service sync is not enabled, do not publish outbox jobs
        if ($this->app['config']->get('applications.acs.sync_enabled') === false)
        {
            return;
        }

        $metricDimensions = array_merge([Metric::LABEL_RZP_MODE => $mode], $metadata);
        $payloadMetadata  = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);
        try {
            // TODO: verify and update as per sync request proto
            $jobPayload = [
                'account_id' => $accountId,
                'mode' => $mode,
                'mock' => false,
                'metadata' => $payloadMetadata,
            ];

            // this needs to be in a transaction due to a hard check in outbox implementation
            // Also, since we are not using the any entities to do db operations,
            // we cannot use $entityRepo->connection()->transaction() as this will
            // default to the connection based on basic auth mode set
            $this->app['repo']->transactionOnConnection(function () use ($mode, $jobPayload) {
                $this->outbox->send(self::OUTBOX_JOB_NAME, $jobPayload, $mode, false);
            }, $mode);

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

    public function logEntityFetch(array $logData)
    {
        if ((config('app.acs.verbose_log') === true) or ($this->stats['total']['count'] > 0))
        {
            app('trace')->info(TraceCode::ACS_ENTITY_FETCH, $logData);
        }
    }

    public function logEntityUpdate(array $logData)
    {
        if ((config('app.acs.verbose_log') === true) or ($this->stats['total']['count'] > 0))
        {
            app('trace')->info(TraceCode::ACS_ENTITY_UPDATE, $logData);
        }
    }

    public function getLogData(PublicEntity $entity, PublicCollection $collection = null)
    {
        if ($collection == null)
        {
            $collection = new PublicCollection;
        }
        $runningInQueue = app()->runningInQueue();
        $logData = ['route' => 'none', 'async_job_name' => 'none'];
        if ($runningInQueue === true)
        {
            $logData['async_job_name'] = app('worker.ctx')->getJobName();
            $logData['mode'] = app('worker.ctx')->getMode();
        }
        else
        {
            $logData['route'] = app('request.ctx')->getRoute();
            $logData['internal_app_name'] = app('request.ctx')->getInternalAppName();
            $logData['mode'] = app('request.ctx')->getMode();
        }
        $logData['connection'] = $entity->getConnection()->getName();
        $logData['is_transaction_active'] = app('repo')->isTransactionActive();
        $logData['stats'] = $this->stats;

        $logData['entity'] = [
            'name' => $entity->getEntityName(),
            'id' => $entity->getId(), 'merchant_id' => $entity->getMerchantId(),
            'collection' => [
                'ids' => $collection->map(function($item, $key) {return $item->getId();})->all(),
                'merchant_ids' => $collection->map(function ($item, $key){return $item->getMerchantId();})->all(),
            ]
        ];

        return $logData;
    }

}
