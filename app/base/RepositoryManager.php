<?php

namespace Base;

use Constants\Entity;
use EE\Exception;

class RepositoryManager extends \Illuminate\Support\Manager
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    public function __get($entity)
    {
        return $this->driver($entity);
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException(
            'No default repository driver');
    }

    protected function createDriver($driver)
    {
        $repo = Entity::getEntityRepository($driver);

        return new $repo($this->app);
    }

    public function saveOrFail($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->saveOrFail($entity);
    }

    public function save($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->save($entity);
    }

    protected function getRepositoryClassFromObject($entityObject)
    {
        $entity = $entityObject->getEntityName();

        return $this->driver($entity);
    }
}
