<?php

namespace RZP\Base;

use DB;
use Illuminate\Support\Facades\App;

use RZP\Models;
use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;
use RZP\Trace\Trace;

class Repository extends \Razorpay\Spine\Repository
{
    use RepositoryFetch;

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
        // we have $this->repo being used for creating queries.
        // Once we shift to the new way of querying via newQuery()
        // then we can change this back to $this->repo. Till then,
        // we will need to keep use of $this->manager to minimum.
        //
        $this->manager = $this->app['repo'];
    }

    public static function getTableNameForEntity(string $entity)
    {
        return E::getTableNameForEntity($entity);
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

    public function findOrFailPublicWithRelations(
        string $id,
        array $relations = [],
        array $columns = array('*'))
    {
        $query = $this->newQuery();

        if (empty($relations) === false)
        {
            $query->with($relations);
        }

        return $query->findOrFailPublic($id, $columns);
    }

    public function findMany($ids, $columns = array('*'))
    {
        return $this->newQuery()->findMany($ids, $columns);
    }

    public function findManyWithRelations($ids, $relations, $columns = array('*'))
    {
        $query = $this->newQuery();

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->findMany($ids, $columns);
    }

    public function findManyByPublicIds($ids)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSignMultiple($ids);

        return $this->findMany($ids);
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

    public function sync($entity, $relation, $ids = [])
    {
        $entity->$relation()->sync($ids);

        return $this;
    }

    public function attach($entity, $relation, $id, array $attributes = [], $touch = true)
    {
        $entity->$relation()->attach($id, $attributes, $touch);

        return $this;
    }

    public function getEntityClass()
    {
        return E::getEntityClass($this->entity);
    }

    public function getTableName()
    {
        return E::getTableNameForEntity($this->entity);
    }

    /**
     * Instantiates a query with an entity having timestamps set to false.
     * This is to avoid setting the updated_at field.
     * @return Query\Builder queryBuilder object
     */
    public function newQueryWithoutTimestamps()
    {
        $entity = $this->getEntityObject();

        $entity->timestamps = false;

        return $entity->setConnection($this->connection)->newQuery();
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
        throw new Exception\DbQueryException($e);
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

    public function fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip = 0, $relations = [])
    {
        $query = $this->getFetchBetweenTimestampQuery($merchantId, $from, $to);

        if (count($relations) > 0)
        {
            $query->with(...$relations);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->get();
    }

    public function fetchAssociatedRelations($entities, $relation, $idCol = 'entity_id', $typeCol = 'type')
    {
        $relationships = array();
        $objects = array();

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        foreach ($entities as $entity)
        {
            $relationships[$entity->$typeCol][] = $entity->$idCol;
        }

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        foreach ($relationships as $type => $ids)
        {
            $typeEntities = $this->manager->$type->findMany($ids);

            foreach ($typeEntities as $entity)
            {
                $objects[$entity->getId()] = $entity;
            }
        }

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        foreach ($entities as $entity)
        {
            $typeEntity = $objects[$entity->$idCol];

            $entity->setRelation($relation, $typeEntity);
        }

        $this->trace->info(
            TraceCode::MERCHANT_REPORT_GENERATION,
            ['time' => time()]);

        return $entities;
    }

    public function fetchBetweenTimestamp($merchantId, $from, $to)
    {
        return $this->getFetchBetweenTimestampQuery($merchantId, $from, $to)
                    ->get();
    }

    /**
     * Selects entity with FOR UPDATE lock.
     * - If other sessions have already acquired LOCK FOR UPDATE on this entity,
     *   this will wait till that gets free and so avoids bad reads.
     * - If this session has acquired the lock first, others will wait (Same as
     *   above).
     *
     * Also, setRawAttributes is being used because of the way PHP handles pass
     * by reference for objects. If the passed object is ASSIGNED to another
     * object/value, the original object from the calling function remains
     * unaffected. Any change ON the passed object will affect the original
     * object too.
     *
     * @param Models\Base\PublicEntity $entity
     * @param bool|boolean             $withTrashed
     *
     * @return null
     *
     * @throws Exception\LogicException
     */
    public function lockForUpdateAndReload(
        Models\Base\PublicEntity $entity,
        bool $withTrashed = false)
    {
        $lockedEntity = $this->lockForUpdate($entity->getId(), $withTrashed);

        $entity->setRawAttributes($lockedEntity->getAttributes(), true);
    }

    /**
     * Fetches entity with given id with a MySQL lock for update
     *
     * @param string $id
     * @param bool   $withTrashed - Whether to include soft deleted results?
     *
     * @return Models\Base\PublicEntity
     * @throws Exception\LogicException
     */
    public function lockForUpdate(string $id, bool $withTrashed = false)
    {
        if ($this->isTransactionActive() === false)
        {
            throw new Exception\LogicException('Attempted lock-for-update outside a DB transaction');
        }

        $query = $this->newQuery()->lockForUpdate();

        if ($withTrashed)
        {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    protected function getFetchBetweenTimestampQuery($merchantId, $from, $to)
    {
        return $this->newQuery()
                    ->betweenTime($from, $to)
                    ->merchantId($merchantId);
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
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ES_SAVE_FAILED,
                $entity->toArray()
            );
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

    /**
     * Override this method in entity/repository in case the type name is
     * different for that entity.
     *
     * @return string
     */
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

    protected function getAttributeWithTableName($col)
    {
        return $this->getTableName() . '.' . $col;
    }

    protected function validateInstanceIsOfCurrentEntity(Models\Base\Entity $entity)
    {
        if ($entity->getEntityName() !== $this->entity)
        {
            throw new Exception\LogicException(
                'Can only handle ' . $this->entity . ' entities here. Provided: ' . $entity->getEntityName());
        }
    }

    protected function validateIdGenerated($entity)
    {
        if ($entity->getKey() === null)
        {
            throw new Exception\LogicException(
                'Unique id not generated for the entity');
        }
    }
}
