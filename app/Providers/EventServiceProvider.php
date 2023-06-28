<?php

namespace RZP\Providers;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events as QueueEvents;
use Illuminate\Cache\Events as CacheEvents;
use Illuminate\Console\Events as ConsoleEvents;
use Illuminate\Database\Events as DatabaseEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use RZP\Events;
use RZP\Events\Kafka as KafkaEvents;
use RZP\Jobs\Job;
use RZP\Listeners;
use RZP\Events\P2p;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Merchant;
use RZP\Models\Partner;
use RZP\Models\Terminal;
use RZP\Modules\Acs;

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

        Events\EntityInstrumentationEvent::class => [
            Listeners\EntityInstrumentationListener::class,
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
            Acs\TriggerSyncListener::class,
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

        P2p\DeviceVerificationCompleted::class => [
            Listeners\P2pWebhookListener::class,
            Listeners\P2pNotificationListener::class,
        ],
        P2p\DeviceDeregistrationCompleted::class => [
            Listeners\P2pWebhookListener::class,
        ],
        P2p\TransactionCreated::class => [
            Listeners\P2pWebhookListener::class,
            Listeners\P2pNotificationListener::class,
        ],
        P2p\TransactionCompleted::class => [
            Listeners\P2pWebhookListener::class,
            Listeners\P2pNotificationListener::class,
            Listeners\P2pReminderListener::class,
        ],
        P2p\TransactionFailed::class => [
            Listeners\P2pWebhookListener::class,
            Listeners\P2pNotificationListener::class,
        ],
        P2p\VpaCreated::class        => [
            Listeners\P2pWebhookListener::class,
        ],
        P2p\VpaDeleted::class        => [
            Listeners\P2pWebhookListener::class,
        ],
        P2p\DeviceCooldownCompleted::class => [
            Listeners\P2pNotificationListener::class,
        ],
        P2p\MerchantComplaintNotification::class => [
            Listeners\P2pWebhookListener::class,
        ],
        AccessMap\EventSaved::class => [
            Listeners\AccessMapListener::class . '@onSaved',
        ],
        AccessMap\EventDeleted::class => [
            Listeners\AccessMapListener::class . '@onDeleted',
        ],
        Partner\Config\EventSaved::class => [
            Listeners\PartnerConfigListener::class . '@onSaved',
        ],
        DatabaseEvents\QueryExecuted::class => [
            Listeners\DatabaseEventListener::class,
        ],
        Acs\RecordSyncEvent::class => [
            Acs\RecordSyncListener::class,
        ],
        Acs\RollbackEvent::class => [
            Acs\RollbackEventListener::class,
        ],
        Acs\CommitEvent::class => [
            Acs\CommitEventListener::class,
        ],
        KafkaEvents\JobProcessed::class => [
            Acs\TriggerSyncListener::class,
        ],
        Acs\TriggerSyncEvent::class => [
            Acs\TriggerSyncListener::class,
        ],
        Merchant\EventSaved::class => [
            Listeners\MerchantEventListener::class . '@onSaved',
        ],
        Merchant\Detail\EventSaved::class => [
            Listeners\MerchantDetailEventListener::class . '@onSaved',
        ],
        Terminal\EventRetrieved::class => [
            Listeners\TerminalsEventListener::class . '@onRetrieved',
        ],
        Terminal\EventSaved::class => [
            Listeners\TerminalsEventListener::class . '@onSaved',
        ],
        ConsoleEvents\CommandFinished::class => [
            Acs\TriggerSyncListener::class,
        ]
    ];

    public function boot()
    {
        parent::boot();

        Queue::after(function (QueueEvents\JobProcessed $event)
        {
            $this->sendLumberJackEvents();

            $this->resetModePostSyncQueueProcessed($event);
        });

        Queue::failing(function (QueueEvents\JobFailed $event)
        {
            $this->sendLumberJackEvents();
        });

        Queue::exceptionOccurred(function (QueueEvents\JobExceptionOccurred $event)
        {
            $this->sendLumberJackEvents();
        });
    }

    /**
     * In case of async requests send the lubmerjack events from the worker.
     */
    protected function sendLumberJackEvents()
    {
        $this->app['diag']->buildRequestAndSend();
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
        // Todo:: to handle encrypted payload data
        $resolvedJob  = unserialize($job->payload()['data']['command']); // nosemgrep : php.lang.security.unserialize-use.unserialize-use

        if ($resolvedJob instanceof Job === true)
        {
            $previousMode = $resolvedJob->getPreviousMode();

            $this->app['basicauth']->setModeAndDbConnection($previousMode);
        }
    }
}
