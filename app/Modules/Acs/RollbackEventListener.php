<?php

namespace RZP\Modules\Acs;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;

class RollbackEventListener
{
    const ASV_OUTBOX_JOB_NAME = 'acs.sync_account.v1';

    public $app;
    public $trace;
    public $outbox;

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->outbox = $this->app['outbox'];
    }

    /**
     * @param RollbackEvent $event
     * @return void
     */
    public function handle(RollbackEvent $event)
    {
        // Ignore rollback for test entity
        if ($event->entity->getConnectionName() === Mode::TEST) {
            return;
        }

        $accountId = $event->entity->getMerchantId();

        $this->trace->info(TraceCode::ASV_ROLLBACK_ENTITY, [
            'entity_name' => $event->entity->getEntityName(),
            'event_id' => $event->eventId,
            'operation' => $event->operation,
        ]);

        $metadata = [
            Metric::LABEL_ASYNC_JOB_NAME => 'none',
            Metric::LABEL_ROUTE => 'none',
            Metric::LABEL_RZP_INTERNAL_APP_NAME => 'none'
        ];

        try {
            $runningInQueue = app()->runningInQueue();
            if ($runningInQueue === true) {
                $metadata[Metric::LABEL_ASYNC_JOB_NAME] = app('worker.ctx')->getJobName();
            } else {
                $metadata[Metric::LABEL_ROUTE] = app('request.ctx')->getRoute();
                $metadata[Metric::LABEL_RZP_INTERNAL_APP_NAME] = app('request.ctx')->getInternalAppName();
            }

            $payloadMetadata = array_merge(['request_id' => $this->app['request']->getId(), 'task_id' => $this->app['request']->getTaskId()], $metadata);
            $jobPayload = [
                'account_id' => $accountId,
                'mode' => Mode::LIVE,
                'mock' => false,
                'metadata' => $payloadMetadata,
            ];
            //TODO: remove the comment when SaveOrFail, DeleteOrFail wrapper is implemented in API
            //$this->publishOutboxJobForRollback(self::ASV_OUTBOX_JOB_NAME, $jobPayload, Mode::LIVE, $metadata);
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_ROLLBACK_EVENT_LISTENER_EXCEPTION, $event->entity);

        }
    }

    public function publishOutboxJobForRollback(string $jobName, array $jobPayload, string $mode, array $metadata)
    {

        $metricDimensions = array_merge([
            Metric::LABEL_RZP_MODE => $mode,
            Metric::LABEL_OUTBOX_JOB => $jobName,
        ], $metadata);
        $logDimensions = [
            'job_name' => $jobName,
            'job_payload' => $jobPayload
        ];
        try {
            // start transaction and push to outbox
            $this->app['repo']->transactionOnConnection(function () use ($mode, $jobName, $jobPayload) {
                $this->outbox->send($jobName, $jobPayload, $mode, false);
            }, $mode);

            $this->trace->info(TraceCode::ASV_ROLLBACK_EVENT_PUBLISHED, $logDimensions);
            $this->trace->count(Metric::ASV_ROLLBACK_EVENT_PUBLISHED, $metricDimensions);
        } catch (\Throwable $e) {
            // Just logging and ignoring exception here to not mess with request flow
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_ROLLBACK_EVENT_PUBLISH_FAILED, $logDimensions);
            $this->trace->count(Metric::ASV_ROLLBACK_EVENT_PUBLISH_FAILED, $metricDimensions);
        }
    }
}
