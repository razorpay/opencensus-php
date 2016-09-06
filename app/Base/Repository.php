<?php

namespace RZP\Base;

use RZP\Constants\Entity as E;
use RZP\Constants\Table;
use DB;
use Illuminate\Support\Facades\App;
use RZP\Trace\TraceCode;
use RZP\Exception\DbQueryException;

class Repository extends \Razorpay\Spine\Repository
{
    protected $app;

    protected $db;

    protected $auth;

    protected $trace;

    protected $queue;

    protected $manager;

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->auth = $this->app['basicauth'];

        $this->queue = $this->app['queue'];

        //
        // Currently, using $this->manager because
        // we have $this->repo being used for creting queries.
        // Once we shift to the new way of querying via newQuery()
        // then we can change this back to $this->repo. Till then,
        // we will need to keep use of $this->manager to minimum.
        //
        $this->manager = $this->app['repo'];
    }

    public function createOrFail(array $attributes)
    {
        $class = $this->getEntityClass();

        $entity = new $class($attributes);

        $this->saveOrFail($entity);

        return $entity;
    }

    public function findOrFailPublic($id, $columns = array('*'))
    {
        return $this->newQuery()->findOrFailPublic($id, $columns);
    }

    public function findMany($ids, $columns = array('*'))
    {
        return $this->newQuery()->findMany($ids, $columns);
    }

    protected function processDbQueryFailure($operation, $attributes = null)
    {
        $e = $this->getExceptionDataArray($operation, $attributes);

        $this->throwException($e);
    }

    protected function getExceptionDataArray($operation, $attributes = null)
    {
        $e = array(
                'model' => get_class($this),
                'operation' => $operation,
                'attributes' => $attributes);

        return $e;
    }

    protected function throwException(array $e)
    {
        throw new DbQueryException($e);
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

    public function fetchBetweenTimestampWithRelations($merchantId, $from, $to, $relations = [])
    {
        $query = $this->getFetchBetweenTimestampQuery($merchantId, $from, $to);

        if (count($relations) > 0)
        {
            $query->with(...$relations);
        }

        return $query->get();
    }

    public function fetchAssociatedRelations($entities, $relation, $idCol = 'entity_id', $typeCol = 'type')
    {
        $relationships = array();
        $objects = array();

        foreach ($entities as $entity)
        {
            $relationships[$entity->$typeCol][] = $entity->$idCol;
        }

        foreach ($relationships as $type => $ids)
        {
            $repo = E::getEntityRepository($type);

            $typeEntities = (new $repo)->findMany($ids);

            foreach ($typeEntities as $entity)
            {
                $objects[$entity->getId()] = $entity;
            }
        }

        foreach ($entities as $entity)
        {
            $typeEntity = $objects[$entity->$idCol];

            $entity->setRelation($relation, $typeEntity);
        }

        return $entities;
    }

    public function fetchBetweenTimestamp($merchantId, $from, $to, $relations = [])
    {
        return $this->getFetchBetweenTimestampQuery($merchantId, $from, $to)
                    ->get();
    }

    protected function getFetchBetweenTimestampQuery($merchantId, $from, $to)
    {
        return $this->newQuery()
                    ->betweenTime($from, $to)
                    ->merchantId($merchantId);
    }

    public function saveOrFail($entity, array $options = array())
    {
        // Gets the attributes which are being newly inserted or updated.
        $dirty = $entity->getDirty();

        // Saves the entity in MySql.
        $entity->saveOrFail($options);

        // [Queue] saves in ES if certain conditions are met.
        $this->saveInEs($entity, $dirty);
    }

    protected function saveInEs($entity, $dirty)
    {
        try
        {
            // Checks if whitelisted es params is set. If yes, checks if $dirty contains any of them.
            if ((isset($this->esWhitelistedParams) === true) and
                (empty(array_intersect(array_keys($dirty), $this->esWhitelistedParams)) === false))
            {
                $esRepoClassPath = $this->getEsRepoClassPath();

                $esType = $this->getEsType();

                $queueData = [
                    'es_type'           => $esType,
                    // This entity object is converted into an array because Queue::push
                    // decodes and encodes it with assoc array flag set to true.
                    'entity'            => $entity->toArray(),
                    'mode'              => $this->app['rzp.mode'],
                ];

                // Saving the entity in ES.
                $this->queue->push($esRepoClassPath.'@fireStoreEntity', $queueData);
            }
        }
        catch (\Exception $ex)
        {
            // Shouldn't fail for any reason
            $this->trace->error(
                TraceCode::ES_SAVE_FAILED,
                $entity->toArray());

            $this->trace->traceException($ex);
        }
    }

    protected function getEsRepoClassPath()
    {
        $parentNamespace = $this->getParentNamespace();

        $esRepoClassPath = $parentNamespace . '\\' . 'EsRepository';

        return $esRepoClassPath;
    }

    protected function getParentNamespace()
    {
        // get_called_class gives the (namespace+classname)
        // removing the last element to get only the namespace.
        return join('\\', explode('\\', get_called_class(), -1));
    }

    // Override this method in entity/repository in case the type name is different for that entity.
    protected function getEsType()
    {
        $parentNamespace = $this->getParentNamespace();

        $parentNamespaceArray = explode('\\', $parentNamespace);

        // Constant names are all uppercase.
        // Table constant class has the same name as the entity class name.
        $className = strtoupper(end($parentNamespaceArray));

        // The ES type name is the same as the table name for the entity in MySQL.
        $typeName = constant("RZP\\Constants\\Table::$className");

        return $typeName;
    }
}
