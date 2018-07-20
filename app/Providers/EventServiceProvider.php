<?php

namespace RZP\Providers;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Cache\Events as CacheEvents;
use Illuminate\Queue\Events as QueueEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use RZP\Events;
use RZP\Jobs\Job;
use RZP\Listeners;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event subscriber mappings for the application.
     * TODO: Replace api.* with specific event subscribers
     *
     * @var array
     */
    protected $subscribe = [
        Listeners\ApiEventSubscriber::class,
    ];

    protected $listen = [
        Events\AuditLogEntry::class => [
            Listeners\AuditLogListener::class,
        ],

        CacheEvents\CacheHit::class => [
            Listeners\CacheEventListener::class,
        ],

        CacheEvents\CacheMissed::class => [
            Listeners\CacheEventListener::class,
        ],

        CacheEvents\KeyWritten::class => [
            Listeners\CacheEventListener::class,
        ],

        CacheEvents\KeyForgotten::class => [
            Listeners\CacheEventListener::class,
        ],

        QueueEvents\JobProcessed::class => [
            Listeners\QueueEventListener::class,
        ],

        QueueEvents\JobProcessing::class => [
            Listeners\QueueEventListener::class,
        ],

        QueueEvents\JobFailed::class => [
            Listeners\QueueEventListener::class,
        ],

        // TODO: Fix it! Looping event won't have $job instance
        // QueueEvents\Looping::class => [
        //     Listeners\QueueEventListener::class,
        // ],

        QueueEvents\JobExceptionOccurred::class => [
            Listeners\QueueEventListener::class,
        ],
    ];

    public function boot()
    {
        parent::boot();

        Queue::after(function (QueueEvents\JobProcessed $event)
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
     * @param QueueEvents\JobProcessed $event
     */
    protected function resetModePostSyncQueueProcessed(QueueEvents\JobProcessed $event)
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

        if ($resolvedJob instanceof Job === true)
        {
            $previousMode = $resolvedJob->getPreviousMode();

            $this->app['basicauth']->setModeAndDbConnection($previousMode);
        }
    }
}
