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
        $shouldCallAsv = (new AsvRouter())->shouldRouteReloadToAsv($this->entity);
        if ($shouldCallAsv === true) {
            try {

                $relationArray = $relations;
                if (is_string($relations)) {
                    $relationArray = [$relations];
                }

                $asvRelations    = array_filter($relationArray, function ($relation) {
                    return in_array($relation, self::ASV_RELATIONS);
                });
                $nonAsvRelations = array_filter($relationArray, function ($relation) {
                    return !in_array($relation, self::ASV_RELATIONS);
                });

                foreach ($asvRelations as $relation) {
                    // unset relation
                    parent::unsetRelation($relation);
                    // load relation again
                    $this->$relation;
                }
                return parent::load($nonAsvRelations);
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
        return parent::load($relations);
    }
}
