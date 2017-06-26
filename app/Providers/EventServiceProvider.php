<?php

namespace RZP\Providers;

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
}
