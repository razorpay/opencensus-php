<?php

namespace RZP\Models\Base;

use App;
use RZP\Exception;

class Observer
{
    protected $app;

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    /**
     * Used to flush the cache on updates, for entities which are using
     * query cache. We flush the cache by deleting all keys with the tag
     * <entity_name>_<entity_id>
     *
     * @param  PublicEntity $entity
     */
    public function updated($entity)
    {
        $this->validateEntity($entity);

        $entity->flushCache($entity->getEntity() . '_' . $entity->getId());
    }

    protected function validateEntity($entity)
    {
        if (($entity instanceof PublicEntity) === false)
        {
            throw new Exception\RuntimeException('Entity should be instance of PublicEntity', [
                'entity' => $entity
            ]);
        }
    }
}
