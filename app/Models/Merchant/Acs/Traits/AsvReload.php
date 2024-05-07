<?php

namespace RZP\Models\Merchant\Acs\Traits;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Trace\TraceCode;
use App;

trait AsvReload
{
    public function reload()
    {
        //Reloading Entity
        $shouldCallAsv = (new AsvRouter())->shouldRouteReloadToAsv($this->entity);
        if($shouldCallAsv)
        {
            try {
                $this->getEntityDetailsForReload();
                return $this;
            } catch (\Exception $e) {
                app('trace')->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_RELOAD_EXCEPTION, [
                    "entity" => $this->entity
                ]);

                app('trace')->count(Metric::ASV_REQUEST_NOT_ROUTED, [
                    'routeOrWorkerName' => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute(),
                    'reason' => 'GOT_EXCEPTION',
                ]);
            }
        }
        return parent::reload();
    }

    public function refresh()
    {
        $shouldCallAsv = (new AsvRouter())->shouldRouteReloadToAsv($this->entity);
        if($shouldCallAsv)
        {
            try {
                $instance = $this->getEntityDetailsForReload();
                $this->relations = $instance->relations;
                return $this;
            } catch (\Exception $e) {
                app('trace')->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_REFRESH_EXCEPTION, [
                    "entity" =>  $this->entity
                ]);

                app('trace')->count(Metric::ASV_REQUEST_NOT_ROUTED, [
                    'routeOrWorkerName' => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute(),
                    'reason' => 'GOT_EXCEPTION',
                ]);
            }
        }
        return parent::refresh();
    }

    public function getEntityDetailsForReload()
    {
        $repo = app('repo');
        $repoName = $this->entity;

        $instance = $repo->$repoName->find($this->getId());
        $this->attributes = $instance->attributes;
        $this->original = $instance->original;
        return $instance;
    }
}
