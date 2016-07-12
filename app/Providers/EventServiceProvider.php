<?php

namespace RZP\Providers;

use Trace;
use Queue;
use RZP\Trace\TraceCode;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'api.*' => [
            'RZP\Models\Event\ApiEventSubscriber@onEvent',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        Queue::failing(function ($failedJob) {
            Trace::error(TraceCode::QUEUE_JOB_FAILURE, $failedJob->data);
        });

        Queue::looping(function ($failedJob) {
            Trace::info(TraceCode::QUEUE_JOB_LOOPING);
        });
    }
}
