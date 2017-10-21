<?php

namespace RZP\Providers;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        ]
    ];

    public function boot()
    {
        parent::boot();

        Queue::after(function (JobProcessed $event)
        {
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
