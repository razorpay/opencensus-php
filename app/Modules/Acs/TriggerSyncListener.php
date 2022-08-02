<?php

namespace RZP\Modules\Acs;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Queue\Events\JobProcessed as QueueJobProcessed;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Events\Kafka\JobProcessed as KafkaJobProcessed;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class TriggerSyncListener
{
    protected $app;

    /** @var SyncEventManager $syncEventManager */
    protected $syncEventManager;

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
        $this->syncEventManager = $this->app[SyncEventManager::SINGLETON_NAME];
    }

    public function handle($event)
    {
        $this->syncEventManager->publishOutboxJobs($this->getMetadata($event));
    }

    protected function getMetadata($event): array
    {
        $metadata = [];
        try
        {
            $metadata = $this->getMetadataForEvent($event);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::ACS_SYNC_METADATA_ERROR);
        }

        $metadata[Metric::LABEL_ROUTE] =
            $metadata[Metric::LABEL_ROUTE] ?? Metric::LABEL_NONE_VALUE;
        $metadata[Metric::LABEL_RZP_INTERNAL_APP_NAME] =
            $metadata[Metric::LABEL_RZP_INTERNAL_APP_NAME] ?? Metric::LABEL_NONE_VALUE;
        $metadata[Metric::LABEL_ASYNC_JOB_NAME] =
            $metadata[Metric::LABEL_ASYNC_JOB_NAME] ?? Metric::LABEL_NONE_VALUE;
        return $metadata;
    }

    protected function getMetadataForEvent($event): array
    {
        $metadata = [];
        if (is_a($event, TriggerSyncEvent::class))
        {
            $requestContext = $this->app['request.ctx'];
            if (empty($requestContext) === false)
            {
                $metadata[Metric::LABEL_RZP_INTERNAL_APP_NAME] = $requestContext->getInternalAppName();
                $metadata[Metric::LABEL_ROUTE] = $requestContext->getRoute();
            }
        }
        elseif (is_a($event, QueueJobProcessed::class) or is_a($event, KafkaJobProcessed::class))
        {
            $jobName = get_class($event->job);
            if (method_exists($event->job, 'resolveName')){
                $jobName = $event->job->resolveName();
            }
            else if (method_exists($event->job, 'getJobName'))
            {
                $jobName = $event->job->getJobName();
            }
            $jobName = str_replace('\\', '_', $jobName);
            $metadata[Metric::LABEL_ASYNC_JOB_NAME] = $jobName;
        }
        elseif(is_a($event, CommandFinished::class))
        {
            $metadata[Metric::LABEL_ASYNC_JOB_NAME] = 'CommandFinished';
        }
        else
        {
            //
            // We do str_replace \ with _ to ease querying, otherwise while querying
            // need to backslash which is difficult from Grafana dashboard.
            //
            $eventName = str_replace('\\', '_', get_class($event));
            $this->app['trace']->count(Metric::ACS_SYNC_ALERT_UNKNOWN_TRIGGER,
                [Metric::LABEL_EVENT_NAME => $eventName]);
            $this->app['trace']->critical(TraceCode::ACS_SYNC_UNKNOWN_TRIGGER,
                [Metric::LABEL_EVENT_NAME => $eventName]);
        }

        return $metadata;
    }
}
