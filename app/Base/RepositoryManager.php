<?php

namespace RZP\Base;

use Closure;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Exception;

class RepositoryManager extends \Illuminate\Support\Manager
{
    public function __construct($app)
    {
        parent::__construct($app);

        $this->db = $app['db'];
    }

    public function __get($entity)
    {
        return $this->driver($entity);
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default repository driver');
    }

    protected function createDriver($driver)
    {
        $repo = Entity::getEntityRepository($driver);

        return new $repo($this->app);
    }

    public function saveOrFail($entity, array $options = array())
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->saveOrFail($entity, $options);
    }

    public function save($entity, array $options = array())
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->save($entity, $options);
    }

    public function delete($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->delete($entity);
    }

    public function deleteOrFail($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->deleteOrFail($entity);
    }

    public function pushOrFail($entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        $repo->pushOrFail($entity);
    }

    public function saveOrFailCollection($collection)
    {
        foreach ($collection->all() as $entity)
        {
            $this->saveOrFail($entity);
        }
    }

    public function reload(& $entity)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        $reloadedEntity = $repo->findOrFail($entity->getKey());

        $attributes = $reloadedEntity->getAttributes();

        $entity->setRawAttributes($attributes, true);

        return $entity;
    }

    public function determineLiveOrTestModeForEntity($id, $entity)
    {
        $repo = $this->driver($entity);

        $obj = $repo->connection(Mode::LIVE)->find($id);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $repo->connection(Mode::TEST)->find($id);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        return null;
    }

    protected function getRepositoryClassFromObject($entityObject)
    {
        $entity = $entityObject->getEntityName();

        return $this->driver($entity);
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();

        return $this;
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollback()
    {
        $this->db->rollback();
    }

    /**
     * Execute a callable within a transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        if ((is_object($callback) === false) or
            ($callback instanceof Closure === false))
        {
            //
            // It's a callable not closure. Wrap it in closure because
            // transaction function in db only accepts closures.
            //
            $result = $this->db->transaction(function() use ($callback)
            {
                return call_user_func($callback);
            });
        }
        else
        {
            $result = $this->db->transaction($callback);
        }

        return $result;
    }

    public function transactionOnLiveAndTest(callable $callback)
    {
        $this->db->connection(Mode::TEST)->beginTransaction();
        $this->db->connection(Mode::LIVE)->beginTransaction();

        // We'll simply execute the given callback within a try / catch block
        // and if we catch any exception we can rollback the transaction
        // so that none of the changes are persisted to the database.
        try
        {
            $result = $callback($this);

            $this->db->connection(Mode::LIVE)->commit();
            $this->db->connection(Mode::TEST)->commit();
        }

        // If we catch an exception, we will roll back so nothing gets messed
        // up in the database. Then we'll re-throw the exception so it can
        // be handled how the developer sees fit for their applications.
        catch (\Exception $e)
        {
            $this->db->connection(Mode::LIVE)->rollBack();
            $this->db->connection(Mode::TEST)->rollBack();

            throw $e;
        }

        return $result;
    }
}
