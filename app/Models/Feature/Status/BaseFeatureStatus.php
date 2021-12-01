<?php

namespace RZP\Models\Feature\Status;

use App;
use RZP\Models\Feature\Entity as FeatureEntity;

abstract class BaseFeatureStatus implements FeatureStatus
{
    protected $app;

    protected $repo;

    protected $trace;

    protected $feature;

    /**
     * BaseFeatureStatus constructor.
     *
     * @param $entity
     */
    public function __construct($entity)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->feature = $entity;
    }

}
