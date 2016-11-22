<?php

namespace RZP\Listeners;

use App;
use RZP\Events\AuditLogEntry;
use Illuminate\Foundation\Bus\DispatchesJobs;
use RZP\Models\Base\EsDao;
use RZP\Constants\Mode;
USE RZP\Trace\TraceCode;


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

    protected $esDao;

    protected $baseIndex;

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

        $this->esDao = new EsDao();
        
        $config = $this->app['config'];

        $mode = (empty($this->app['rzp.mode'] === true)) ? Mode::TEST : $this->app['rzp.mode'];

        $this->baseIndex = $config->get('database.es_heimdall')[$mode];
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

        // steps for adding to ES
        // 1. Create an index against the org id (we want different organizations to have different org ids)
        // 2. Store the event data
        // Note: elastic search index names are always in lower case. Hence, to search
        // always convert the index name to lower case and search

        $indexName  = strtolower($this->baseIndex . '_' . $event->admin->org->getId());

        $this->esDao->storeAdminEvent($indexName, $event->admin, $event->action,
                                        $event->customProperties,$this->event->firing());

        $this->trace->info(TraceCode::HEIMDALL_EVENT_RECORD, [$event]);
    }
}
