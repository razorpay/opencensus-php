<?php

namespace RZP\Modules\Acs;

use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Consumer\Service as Consumer;
use RZP\Trace\TraceCode;
use Razorpay\Outbox\Job\Core as Outbox;
use RZP\Models\Merchant\Acs\AsvClient;

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

    /** @var Application $app */
    public $app;

    /** @var Trace $trace */
    public $trace;

    /** @var Outbox $outbox */
    public $outbox;

    public $splitzService;

    public $syncDeviationAsvClient;

    public $repo;


    // accountIds are stored as [id => [outboxJob1, outboxJob2, ...]]
    protected $liveAccountIds = [];
    protected $testAccountIds = [];

    protected $stats = ['total' => ['count' => 0]];

    protected array $currentTransactionStats = [];

    public function __construct(Application $app)
    {
        $this->app                    = $app;
        $this->trace                  = $this->app['trace'];
        $this->outbox                 = $this->app['outbox'];
        $this->splitzService          = $this->app['splitzService'];
        $this->syncDeviationAsvClient = new AsvClient\SyncAccountDeviationAsvClient();
        $this->repo                   = $this->app['repo'];
    }

    public function resetTransactionStats(): void
    {
        $this->currentTransactionStats = [];
    }

    public function __destruct()
    {
        // If there are some unreported sync events, log them.
        // Do not throw exception, it would result in unclean/fatal shutdown.
        if ($this->hasUnreportedAccountIds()) {
            if ($this->hasUnreportedLiveAccountIds()) {
                $this->trace->count(
                    Metric::ACS_SYNC_ALERT_UNREPORTED_ACCOUNTS,
                    [Metric::LABEL_RZP_MODE => Mode::LIVE],
                    count($this->liveAccountIds)
                );
            }

            if ($this->hasUnreportedTestAccountIds()) {
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

    private function mergeOutboxJobs(array &$accountIdMap, string $accountId, array $outboxJobs)
    {
        $accountIdMap[$accountId] = array_key_exists($accountId, $accountIdMap) ?
            array_unique(array_merge($accountIdMap[$accountId], $outboxJobs)) : $outboxJobs;

    }

    /**
     * Records account id to be published for sync
     *
     * @param PublicEntity $entity
     * @param array $outboxJobs
     */
    public function recordAccountSync(PublicEntity $entity, array $outboxJobs)
    {
        $accountId = $entity->getMerchantId();
        $mode      = $entity->getConnectionName();

        switch ($mode) {
            case Mode::LIVE:
                $this->mergeOutboxJobs($this->liveAccountIds, $accountId, $outboxJobs);
                break;
            case Mode::TEST:
                $this->mergeOutboxJobs($this->testAccountIds, $accountId, $outboxJobs);
                break;
            default:
                $this->trace->count(
                    Metric::ACS_SYNC_ALERT_UNKNOWN_MODE,
                    [Metric::LABEL_RZP_MODE => $mode]
                );
                $this->trace->critical(TraceCode::ACS_SYNC_UNKNOWN_MODE, [
                    'accountId' => $accountId,
                    'mode' => $mode,
                    'outbox_jobs' => $outboxJobs,
                ]);
        }

        $this->trace->info(TraceCode::RECORDED_ACCOUNT_ID, [
            'accountId' => $accountId,
            'mode' => $mode,
            'outbox_jobs' => $outboxJobs,
            'entity_name' => $entity->getEntityName(),
        ]);

        // for live mode, store the stats like the number of updates for each entity etc.
        if ($mode == Mode::LIVE) {
            // we log before the stats are updated as we want to see the number of updates
            // already applied in the request flow before the current update
            $logData = $this->getLogData($entity, $outboxJobs);
            $this->logEntityUpdate($logData);
            $entityName = $entity->getEntityName();

            if (array_key_exists($entityName, $this->stats) === false) {
                $this->stats[$entityName] = ['count' => 0];
            }

            $this->stats[$entityName]['count']++;
            $this->stats['total']['count']++;

            if ($this->repo->isTransactionActive() === true) {
                if (array_key_exists($entityName, $this->currentTransactionStats) === false) {
                    $this->currentTransactionStats[$entityName] = ['count' => 0];
                }
                $this->currentTransactionStats[$entityName]['count']++;
            }
        }
    }

    /**
     * Publishes sync events for account ids recorded to outbox.
     * Ideally this should be called only once after all the business logic has completed.
     * @param array $metadata
     */
    public function publishOutboxJobs(array $metadata)
    {
        $acsSyncEnabled        = $this->app['config']->get('applications.acs.sync_enabled');
        $credcaseSyncEnabled   = $this->app['config']->get('applications.acs.credcase_sync_enabled');
        $asvSplitzExperimentId = $this->app['config']->get('applications.acs.splitz_experiment_id');

        foreach ($this->liveAccountIds as $accountId => $outboxJobs) {

            // We record an account id to report a change to ASV if any save entity function is called on one of the
            // merchant entities, however, one or more save function can exist in a transaction. If one of the save
            // call fails, entire transaction is reverted. However, in this case, account_id can be recorded from one
            // of the previous save calls. Giving us an account_id that does not exist in DB, and an unnecessary call to
            // ASV. This call is also recorded a false error, increases error rates on ASV.
            try {
                $this->trace->info(TraceCode::ASV_FIND_ACCOUNT_IN_DB, ['id' => $accountId]);

                $this->repo->merchant->findOrFail($accountId);
            } catch (\Throwable $e) {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ASV_COULD_NOT_FIND_ACCOUNT,
                    [
                        'id' => $accountId,
                    ]
                );

                continue;
            }

            foreach ($outboxJobs as $outboxJob) {
                switch ($outboxJob) {
                    case SyncEventObserver::ACS_OUTBOX_JOB_NAME:
                        $payloadMetadata = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);
                        // TODO: verify and update as per sync request proto
                        $jobPayload = [
                            'account_id' => $accountId,
                            'mode' => Mode::LIVE,
                            'mock' => false,
                            'metadata' => $payloadMetadata,
                        ];
                        //Commenting out the code if required can be again uncommented

                        //    $isSynced = false;
                        //    if ($this->isSplitzOn($asvSplitzExperimentId, $accountId) === true) {
                        //        $isSynced = $this->syncAccountDeviation($acsSyncEnabled, $jobPayload, Mode::LIVE, $metadata);
                        //    }

                        $this->publishOutboxJob($acsSyncEnabled, SyncEventObserver::ACS_OUTBOX_JOB_NAME,
                            $jobPayload, Mode::LIVE, $metadata);


                        break;
                    case SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME:
                        $jobPayload = [
                            'owner_id' => $accountId,
                            'owner_type' => Consumer::ConsumerTypeMerchant,
                            'domain' => Consumer::ConsumerDomainRazorpay,
                        ];
                        $this->publishOutboxJob($credcaseSyncEnabled, SyncEventObserver::CREDCASE_OUTBOX_JOB_NAME,
                            $jobPayload, Mode::LIVE, $metadata);
                        break;
                    default:
                        $this->trace->count(
                            Metric::ACS_SYNC_ALERT_UNKNOWN_OUTBOX_JOB,
                            [Metric::LABEL_OUTBOX_JOB => $outboxJob]
                        );
                        $this->trace->critical(TraceCode::ACS_SYNC_UNKNOWN_OUTBOX_JOB, [
                            'accountId' => $accountId,
                            'mode' => Mode::LIVE,
                            'outbox_job' => $outboxJob,
                        ]);
                }
            }
        }

        // Skipping publishing test accounts because we have decided to only sync live for now
        // Enabling this would need to use test mode outbox instance
//        foreach ($this->testAccountIds as $accountId => $ignoredValue)
//        {
//            $payloadMetadata  = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);
//            $jobPayload = [
//                'account_id' => $accountId,
//                'mode'       => Mode::TEST,
//                'mock'       => false,
//                'metadata'   => $payloadMetadata,
//            ];
//            $this->publishOutboxJob($acsSyncEnabled, self::ACS_OUTBOX_JOB_NAME,
//                $jobPayload, Mode::LIVE, $metadata);
//        }

        $this->resetAccountParams();
    }

    public function resetAccountParams()
    {
        $this->liveAccountIds = [];
        $this->testAccountIds = [];

        $this->stats = ['total' => ['count' => 0]];
    }

    public function publishOutboxJob(bool   $syncEnabled, string $jobName, array $jobPayload,
                                     string $mode, array $metadata)
    {

        // if sync is not enabled, do not publish outbox jobs
        if ($syncEnabled == false) {
            return;
        }

        $metricDimensions = array_merge([
            Metric::LABEL_RZP_MODE => $mode,
            Metric::LABEL_OUTBOX_JOB => $jobName,
        ], $metadata);
        $logDimensions    = [
            'job_name' => $jobName,
            'job_payload' => $jobPayload
        ];
        try {

            // this needs to be in a transaction due to a hard check in outbox implementation
            // Also, since we are not using the any entities to do db operations,
            // we cannot use $entityRepo->connection()->transaction() as this will
            // default to the connection based on basic auth mode set
            $this->app['repo']->transactionOnConnection(function () use ($mode, $jobName, $jobPayload) {
                $this->outbox->send($jobName, $jobPayload, $mode, false);
            }, $mode);

            $this->trace->info(TraceCode::ACS_SYNC_EVENT_PUBLISHED, $logDimensions);
            $this->trace->count(Metric::ACS_SYNC_EVENT_PUBLISHED, $metricDimensions);
        } catch (\Throwable $e) {
            // Just logging and ignoring exception here to not mess with request flow
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ACS_SYNC_EVENT_PUBLISH_FAILED, $logDimensions);
            $this->trace->count(Metric::ACS_SYNC_ALERT_EVENT_PUBLISH_FAILED, $metricDimensions);
        }
    }

    /**
     * @param bool $syncEnabled
     * @param array $jobPayload
     * @param string $mode
     * @param array $metadata
     * @return bool
     */
    public function syncAccountDeviation(bool $syncEnabled, array $jobPayload, string $mode, array $metadata): bool
    {
        // if sync is not enabled, do not sync data with Account Service
        if ($syncEnabled == false) {
            return true;
        }

        $metricDimensions = array_merge([Metric::LABEL_RZP_MODE => $mode], $metadata);

        try {

            $this->trace->info(TraceCode::ASV_CALL_SYNC_ACCOUNT_DEVIATION, $jobPayload);
            $response = $this->syncDeviationAsvClient->syncAccountDeviation($jobPayload);
            $this->trace->info(TraceCode::ASV_CALL_SYNC_ACCOUNT_DEVIATION_SUCCESS, ['response' => $response->serializeToJsonString()]);

            return true;

        } catch (\Throwable $e) {

            // Just logging and ignoring exception here to not mess with request flow
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_CALL_SYNC_ACCOUNT_DEVIATION_ERROR);
            $this->trace->count(Metric::ASV_SYNC_ACCOUNT_DEVIATION_FAILED, $metricDimensions);

            return false;
        }
    }

    /**
     * @param string $experimentId
     * @param string $id
     * @return bool
     */
    public function isSplitzOn(string $experimentId, string $id): bool
    {
        try {
            $input = ['id' => $id, 'experiment_id' => $experimentId];

            $this->trace->info(TraceCode::ASV_SPLITZ_REQUEST, $input);
            $response = $this->splitzService->evaluateRequest($input);
            $this->trace->info(TraceCode::ASV_SPLITZ_RESPONSE, $response);

            if ($response['status_code'] !== 200) {
                return false;
            }

            $variant = $response['response']['variant'] ?? [];

            $variables = $variant['variables'] ?? [];

            foreach ($variables as $variable) {
                $key   = $variable['key'] ?? '';
                $value = $variable['value'] ?? '';

                if ($key === 'enabled' && $value === 'true') {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }
    }


    /**
     * Context for sync event to help with debugging
     * This includes route/job names or other relevant data points.
     */
    protected function getContext(Application $app): array
    {
        $context = [];
        if (isset($app['request.ctx']) and empty($app['request.ctx']) === false) {
            $requestContext                               = $app['request.ctx'];
            $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] = $requestContext->getInternalAppName();
            $context[Metric::LABEL_ROUTE]                 = $requestContext->getRoute();
        }

        if (isset($app['worker.ctx']) and empty($app['worker.ctx']) === false) {
            $workerContext = $app['worker.ctx'];
            $jobName       = get_class($app['worker.ctx']);
            if (method_exists($workerContext, 'getJobName')) {
                $jobName = $workerContext->getJobName();
            }
            $jobName                               = str_replace('\\', '_', $jobName);
            $context[Metric::LABEL_ASYNC_JOB_NAME] = $jobName;
        }

        $context[Metric::LABEL_ROUTE]                 =
            $context[Metric::LABEL_ROUTE] ?? Metric::LABEL_NONE_VALUE;
        $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] =
            $context[Metric::LABEL_RZP_INTERNAL_APP_NAME] ?? Metric::LABEL_NONE_VALUE;
        $context[Metric::LABEL_ASYNC_JOB_NAME]        =
            $context[Metric::LABEL_ASYNC_JOB_NAME] ?? Metric::LABEL_NONE_VALUE;

        return $context;
    }

    public function logEntityFetch(array $logData): void
    {
        $routeName    = $logData['route'] ?? 'none';
        $asyncJobName = $logData['async_job_name'] ?? 'none';
        $entityName   = $logData['entity']['name'] ?? 'none';


        $metricDimensions = [
            Metric::LABEL_ROUTE => $routeName,
            Metric::LABEL_ASYNC_JOB_NAME => $asyncJobName,
        ];

        if (config('applications.acs.read_traffic_metric_enabled', false) === true) {
            app('trace')->count(Metric::MERCHANT_RELATED_ENTITIES_READ_TRAFFIC_TOTAL, $metricDimensions);

            if ($this->repo->isTransactionActive() === true) {
                if (array_key_exists($entityName, $this->currentTransactionStats) === true) {
                    $count = $this->currentTransactionStats[$entityName]['count'] ?? 0;
                    if ($count > 0) {
                        $metricDimensions = [
                            Metric::LABEL_ROUTE => $routeName,
                            Metric::LABEL_ASYNC_JOB_NAME => $asyncJobName,
                            Metric::ENTITY_UPDATED => $entityName,
                        ];
                        app('trace')->info(TraceCode::ACS_READ_AFTER_WRITE_FETCH, [
                            Metric::LABEL_ROUTE => $routeName,
                            Metric::LABEL_ASYNC_JOB_NAME => $asyncJobName,
                            Metric::ENTITY_UPDATED => $entityName,
                            'count' => $count
                        ]);
                        app('trace')->count(Metric::MERCHANT_ENTITIES_READ_AFTER_WRITE_TOTAL, $metricDimensions);
                    }
                }
            }
        }

        if ((config('app.acs.verbose_log') === true) or ($this->stats['total']['count'] > 0)) {
            app('trace')->info(TraceCode::ACS_ENTITY_FETCH, $logData);
        }
    }

    public function logEntityUpdate(array $logData)
    {
        $routeName    = $logData['route'] ?? 'none';
        $asyncJobName = $logData['async_job_name'] ?? 'none';


        $writeHappeningInTransaction = "false";
        if ($this->repo->isTransactionActive() === true) {
            $writeHappeningInTransaction = "true";
        }

        $metricDimensions = [
            Metric::LABEL_ROUTE => $routeName,
            Metric::LABEL_ASYNC_JOB_NAME => $asyncJobName,
            Metric::DB_TRANSACTION => $writeHappeningInTransaction
        ];

        if (config('applications.acs.write_traffic_metric_enabled', false) === true) {
            app('trace')->count(Metric::MERCHANT_RELATED_ENTITIES_WRITE_TRAFFIC_TOTAL, $metricDimensions);
        }

        if ((config('app.acs.verbose_log') === true) or ($this->stats['total']['count'] > 0)) {
            app('trace')->info(TraceCode::ACS_ENTITY_UPDATE, $logData);
        }
    }

    public function getLogData(PublicEntity $entity, array $outboxJobs, PublicCollection $collection = null)
    {
        if ($collection == null) {
            $collection = new PublicCollection;
        }
        $runningInQueue = app()->runningInQueue();
        $logData        = ['route' => 'none', 'async_job_name' => 'none', 'outbox_jobs' => $outboxJobs];
        if ($runningInQueue === true) {
            $logData['async_job_name'] = app('worker.ctx')->getJobName();
            $logData['mode']           = app('worker.ctx')->getMode();
        } else {
            $logData['route']             = app('request.ctx')->getRoute();
            $logData['internal_app_name'] = app('request.ctx')->getInternalAppName();
            $logData['mode']              = app('request.ctx')->getMode();
        }
        $logData['connection']            = $entity->getConnection()->getName();
        $logData['is_transaction_active'] = app('repo')->isTransactionActive();
        $logData['stats']                 = $this->stats;

        $logData['entity'] = [
            'name' => $entity->getEntityName(),
            'id' => $entity->getId(), 'merchant_id' => $entity->getMerchantId(),
            'collection' => [
                'ids' => $collection->map(function ($item, $key) {
                    return $item->getId();
                })->all(),
                'merchant_ids' => $collection->map(function ($item, $key) {
                    return $item->getMerchantId();
                })->all(),
            ]
        ];

        return $logData;
    }

}
