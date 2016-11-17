<?php

namespace RZP\Listeners;

use App;
use RZP\Events\AuditLogEntry;
use Illuminate\Foundation\Bus\DispatchesJobs;


use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLogListener
{
    use DispatchesJobs;

    protected $app;

    /**
     * Event being fired
     * @var string
     */
    protected $event;

    /**
     * Laravel Events instance
     * @var
     */
    protected $events;

    protected $queue;

    protected $trace;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->event = $this->app['events'];

        $this->trace = $this->app['trace'];

        $this->queue = $this->app['queue'];
    }

    /**
     * Handle the event.
     *
     * @param  AuditLogEntry  $event
     * @return void
     */
    public function handle(AuditLogEntry $event)
    {
        // Get event data as $event->data
        // Access data as $event->data;
        // Get dirty data and details from event data
        // save to es
        // $event = $this->event->firing();

        sd($event->admin, $event->action, $this->event->firing());
        // $this->trace->info("EVENT_RECORD", [$event]);
    }
}
