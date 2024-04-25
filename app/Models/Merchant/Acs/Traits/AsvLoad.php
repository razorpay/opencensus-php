<?php

namespace RZP\Models\Merchant\Acs\Traits;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Trace\TraceCode;
use App;

trait AsvLoad
{
    public function load($relations)
    {
        if(is_string($relations) && in_array($relations, self::ASV_RELATIONS)) {
            $shouldCallAsv = (new AsvRouter())->shouldRouteReloadToAsv($this->entity);
            if($shouldCallAsv)
            {
                try{
                    parent::unsetRelation($relations);
                    return $this;
                } catch (\Exception $e) {
                    app('trace')->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_LOAD_EXCEPTION, [
                    "entity" => $this->entity
                    ]);

                    app('trace')->count(Metric::ASV_REQUEST_NOT_ROUTED, [
                        'routeOrWorkerName' => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute(),
                        'reason' => 'GOT_EXCEPTION',
                    ]);
                }
            }
        } else {
            app('trace')->count(Metric::ASV_REQUEST_NOT_ROUTED, [
                'routeOrWorkerName' => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute(),
                'reason' => 'MULTIPLE_RELATIONS'
            ]);
        }
        return parent::load($relations);
    }
}
