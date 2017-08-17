<?php

namespace RZP\Base;

use Closure;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base\PublicEntity;

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

    public function sync($entity, $relation, $ids = [], bool $detaching = true)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->sync($entity, $relation, $ids, $detaching);
    }

    public function detach($entity, $relation, $ids = [])
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->detach($entity, $relation, $ids);
    }

    public function attach($entity, $relation, $id, array $attributes = [], $touch = true)
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->attach($entity, $relation, $id, $attributes, $touch);
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

        return $repo->reload($entity);
    }

    public function loadRelations(PublicEntity $entity): PublicEntity
    {
        $repo = $this->getRepositoryClassFromObject($entity);

        return $repo->loadRelations($entity);
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
     * $callback is not type-hinted as callable to support arrays.
     *
     * @param callable $callback
     * @param array    $params
     *
     * @return mixed
     */
    public function transaction($callback, ...$params)
    {
        if ((is_object($callback) === false) or
            ($callback instanceof Closure === false))
        {
            //
            // It's a callable not closure. Wrap it in closure because
            // transaction function in db only accepts closures.
            //
            $result = $this->db->transaction(function() use ($callback, $params)
            {
                return call_user_func($callback, ...$params);
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
        //
        // We need to grab and assign the default connection here
        // because in the callback code, the functions try to change
        // the default connection. This is again required because of
        // lack of eloquent's support for taking specific connection
        // instance on relationship based queries.
        //
        $currentConnection = $this->getDefaultDbConn();

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
        finally
        {
            $this->setDefaultDbConn($currentConnection);
        }

        return $result;
    }

    protected function getDefaultDbConn()
    {
        return $this->app['config']->get('database.default');
    }

    protected function setDefaultDbConn($conn)
    {
        $this->app['config']->set('database.default', $conn);
    }

    public function isTransactionActive()
    {
        $env = $this->app->environment();

        if ($env === 'testing')
        {
            return ($this->db->transactionLevel() > 1);
        }

        return ($this->db->transactionLevel() > 0);
    }

    public function assertTransactionActive()
    {
        assert ($this->isTransactionActive());
    }
}
