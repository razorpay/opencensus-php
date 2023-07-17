<?php

namespace RZP\Modules\Acs;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;

class RollbackEventListener
{
    const ASV_OUTBOX_JOB_NAME = 'acs.sync_account.v1';

    public $app;
    public $trace;
    public $outbox;

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->outbox = $this->app['outbox'];
    }

    /**
     * @param RollbackEvent $event
     * @return void
     */
    public function handle(RollbackEvent $event)
    {
        // Ignore rollback for test entity
        if ($event->entity->getConnectionName() === Mode::TEST) {
            return;
        }
        try {
            $routeOrJobName = $this->getRouteOrJobName();
            $this->trace->info(TraceCode::ASV_ROLLBACK_EVENT_HANDLER,
                ["entity" => $event->entity?->getEntityName(), 'routeOrJobName' => $routeOrJobName]
            );
            app(SyncEventManager::SINGLETON_NAME)->resetTransactionStats();
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_ROLLBACK_EVENT_LISTENER_EXCEPTION, $event->entity);

        }
    }

    public function getRouteOrJobName()
    {
        try {
            $runningInQueue = app()->runningInQueue();
            if ($runningInQueue === true) {
                $flow = app('worker.ctx')->getJobName();
            } else {
                $flow = app('request.ctx')->getRoute();
            }

            if ($flow === null or $flow === "") {
                return "none";
            }

            return $flow;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_ROLLBACK_GET_ROUTE_OR_WORKER_NAME_EXCEPTION);
            return "none";
        }
    }
}
