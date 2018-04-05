<?php

namespace RZP\Providers;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Metrics;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event subscriber mappings for the application.
     * TODO: Replace api.* with specific event subscribers
     *
     * @var array
     */
    protected $subscribe = [
        'RZP\Listeners\ApiEventSubscriber',
    ];

    protected $listen = [
        'RZP\Events\AuditLogEntry' => [
            'RZP\Listeners\AuditLogListener',
        ],

        'Illuminate\Cache\Events\CacheHit' => [
            'RZP\Listeners\QueryCacheEventListener',
        ],

        'Illuminate\Cache\Events\CacheMissed' => [
            'RZP\Listeners\QueryCacheEventListener',
        ],

        'Illuminate\Cache\Events\KeyWritten' => [
            'RZP\Listeners\QueryCacheEventListener',
        ],

        'Illuminate\Cache\Events\KeyForgotten' => [
            'RZP\Listeners\QueryCacheEventListener',
        ]
    ];

    public function boot()
    {
        parent::boot();

        Queue::before(function (JobProcessing $event)
        {
            //
            // Metrics:
            // Sets Prometheus's storage adapter as in-memory, otherwise we use APCU but for php cli mode apcu is not
            // enabled and in-memory is sufficient.
            //
            $this->app['config']->set('metrics.drivers.prometheus.adapter', 'inmemory');
        });

        Queue::after(function (JobProcessed $event)
        {
            // Metrics: Pushes metrics(if any) collected during the job lifetime to push gateway configured
            Metrics::push();

            $this->resetModePostSyncQueueProcessed($event);
        });
    }

    /**
     * In many cases request context alawys being one of test/live, but we push
     * multiple queue jobs with mode as test and live respectively.
     *
     * Now referring to Jobs\Job.php, in queue jobs we use mode passed as payload
     * to set basic auth's mode and db connection in "queue context". Only in
     * case of sync queue jobs this ends up with issues as both "request" and "queue"
     * context are actually the same. And so we would want to reset the basic
     * auth's mode and db connection to previous value.
     *
     * @param JobProcessed $event
     */
    protected function resetModePostSyncQueueProcessed(JobProcessed $event)
    {
        $job = $event->job;

        if ($job instanceof SyncJob === false)
        {
            return;
        }

        //
        // Actual job class is wrapped under likes of SyncJob, SqsJob classes.
        // Also we only have to deal with Job classes extending our base Job.
        //
        $resolvedJob  = unserialize($job->payload()['data']['command']);

        if ($resolvedJob instanceof \RZP\Jobs\Job === true)
        {
            $previousMode = $resolvedJob->getPreviousMode();

            $this->app['basicauth']->setModeAndDbConnection($previousMode);
        }
    }
}
