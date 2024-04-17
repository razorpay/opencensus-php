<?php

namespace RZP\Models\Merchant\Acs\Traits;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Collection;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use function PHPUnit\Framework\isInstanceOf;

trait AsvEntityConnection
{
    /**
     * @param Collection|PublicCollection|EloquentModel|null $object
     * @param null                                           $connection
     *
     * @return void
     */
    public function resetConnectionOnModels(Collection|PublicCollection|EloquentModel|null $object, $connection = null): void
    {
        if (empty($object))
        {
            return;
        }

        try
        {
            switch (true)
            {
                case $object instanceof PublicCollection:
                case $object instanceof Collection:
                    $object->each(
                        function ($model) use ($connection) {
                            $this->setOldDBConnection($model, $connection);
                        }
                    );

                    break;
                case $object instanceof EloquentModel:
                    $this->setOldDBConnection($object, $connection);
                    break;
                default:
                    $this->trace->info(TraceCode::MODEL_NOT_ELOQUENT_INSTANCE, [
                        "route" => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute()
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR ,TraceCode::SET_PARENT_MODE_FAILURE);
        }
    }

    public function setOldDBConnection($entity, $oldConnection) : void
    {
        try
        {
            if($oldConnection === null)
            {
                $oldConnection = $this->connection;
            }

            if($entity instanceof EloquentModel)
            {
                $entity->setConnection($oldConnection);
            } else {
                $this->trace->info(TraceCode::MODEL_NOT_ELOQUENT_INSTANCE, [
                    "route" => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute()
                ]);
            }
        } catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR ,TraceCode::SET_PARENT_MODE_FAILURE);
        }
    }
}
