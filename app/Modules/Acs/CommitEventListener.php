<?php

namespace RZP\Modules\Acs;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;

class CommitEventListener
{

    public $app;
    public $trace;
    public $outbox;

    public function __construct()
    {
        $this->app    = \App::getFacadeRoot();
        $this->trace  = $this->app['trace'];
        $this->outbox = $this->app['outbox'];
    }

    /**
     * @param CommitEvent $event
     * @return void
     */
    public function handle(CommitEvent $event)
    {
        // Ignore rollback for test entity
        if ($event->entity->getConnectionName() === Mode::TEST) {
            return;
        }
        try {
            $this->trace->info(TraceCode::ASV_COMMIT_EVENT_HANDLER,
                ['entity' => $event->entity?->getEntityName()]
            );
            app(SyncEventManager::SINGLETON_NAME)->resetTransactionStats();

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_COMMIT_EVENT_LISTENER_EXCEPTION, $event->entity);
        }
    }
}
