<?php

namespace RZP\Listeners;

use RZP\Constants\Metric;
use RZP\Models\Pricing\EventDeleted;
use RZP\Models\Pricing\EventSaved;
use RZP\Trace\TraceCode;

class PricingEventListener
{
    public function onSaved(EventSaved $event)
    {
        if (app()->runningUnitTests() === true) {
            return;
        }
        $this->pushMetricsAndLog($event,Metric::PRICING_WRITE_REQUEST);
    }

    public function onDeleted(EventDeleted $event)
    {
        if (app()->runningUnitTests() === true) {
            return;
        }
        $this->pushMetricsAndLog($event, Metric::PRICING_DELETE_REQUEST);
    }

    private function pushMetricsAndLog($event, $metric)
    {
        try {
            app('trace')->count($metric, [
                'route' => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ]);


            if (rand(1, 10000) > 1000) {
                return;
            }
            app('trace')->info(TraceCode::PRICING_ENTITY_EVENT, $this->getLogData($metric));
        } catch(\Throwable $e) {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::PRICING_EVENT_EXCEPTION, []);
        }

    }

    private function getLogData($metric)
    {
        $runningInQueue = app()->runningInQueue();
        $logData = ['route' => 'none', 'async_job_name' => 'none'];
        if ($runningInQueue === true) {
            $logData['async_job_name'] = app('worker.ctx')->getJobName();
            $logData['mode'] = app('worker.ctx')->getMode();
        } else {
            $logData['route'] = app('request.ctx')->getRoute();
            $logData['internal_app_name'] = app('request.ctx')->getInternalAppName();
            $logData['mode'] = app('request.ctx')->getMode();
        }
        $logData['is_transaction_active'] = app('repo')->isTransactionActive();
        $logData['trace'] = $this->getTrace();
        $logData['eventName'] = $metric;

        return $logData;
    }

    protected function getTrace()
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        foreach ($backTrace as $index => $trace) {
            if (isset($trace['class']) && $trace['class'] === 'RZP\Models\Pricing\Repository') {
                return array_slice($backTrace, $index, 5);
            }
        }
        return $backTrace;
    }
}
