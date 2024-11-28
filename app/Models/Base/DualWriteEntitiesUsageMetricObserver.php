<?php

namespace RZP\Models\Base;

use App;
use Illuminate\Support\Facades\Route;
use RZP\Services\DualWriteEntitiesUsageMetric;

// Adding a listener to identify usage of the entities that are dual written between API and PGOS and is not owned by ASV
class DualWriteEntitiesUsageMetricObserver
{
    const CREATE_ACTION     = "create";
    const READ_ACTION       = "read";
    const UPDATE_ACTION     = "update";
    const DELETE_ACTION     = "delete";

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * Listen to the created event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function created($entity)
    {
        $this->setDualWriteEntitiesUsageMetric($entity, self::CREATE_ACTION);
    }

    /**
     * Listen to the retrieved event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function retrieved($entity)
    {
        $this->setDualWriteEntitiesUsageMetric($entity, self::READ_ACTION);
    }

    /**
     * Listen to the deleted event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function deleted($entity)
    {
        $this->setDualWriteEntitiesUsageMetric($entity, self::DELETE_ACTION);
    }

    /**
     * Listen to the updated event.
     *
     * @param  Entity $entity
     * @return void
     */
    public function updated($entity)
    {
        $this->setDualWriteEntitiesUsageMetric($entity, self::UPDATE_ACTION);
    }

    protected function setDualWriteEntitiesUsageMetric($entity, $action)
    {
        $this->app[DualWriteEntitiesUsageMetric::class]->setMetric(
            Route::currentRouteName(),
            $entity->getEntityName(),
            $action);
    }
}
