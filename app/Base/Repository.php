<?php

namespace RZP\Base;

use Config;
use DB;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Models;
use RZP\Models\Base\Es\Repository as EsRepository;
use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Jobs\EsSync;

class Repository extends \Razorpay\Spine\Repository
{
    /**
     * Delay in making es job available for queue consumer.
     * Value is in seconds.
     */
    const ES_JOB_DELAY = 3;

    use RepositoryFetch;
    use DispatchesJobs;

    protected $app;

    protected $db;

    protected $auth;

    protected $trace;

    protected $queue;

    protected $manager;

    /**
     * Corresponding esRepo instance of entity.
     * When intending to use please set it first by calling setEsRepoIfExist().
     *
     * @var EsRepository
     */
    protected $esRepo = null;

    /**
     * Holds list of relations to be loaded with newQuery() (find/fetch).
     * Use like - repo->with([Entity::LINE_ITEMS])->fetch().
     * Optimizes query in general by doing mysql IN() query.
     *
     * @var array
     */
    protected $relations = [];

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

    /**
     * Sets relations property which gets used in newQuery to load relations
     * optimally.
     *
     * @param array $relations
     *
     * @return self
     */
    public function with(array $relations): self
    {
        $this->relations = array_map(
                                function ($v)
                                {
                                    return camel_case($v);
                                },
                                $relations);

        return $this;
    }

    /**
     * Overrides parent's method to use $relations attribute.
     *
     * @return BuilderEx
     */
    public function newQuery(): BuilderEx
    {
        $query = parent::newQuery();

        if (empty($this->relations) === false)
        {
            $query->with($this->relations);
        }

        return $query;
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

    public function findWithRelations(
        string $id,
        array $relations = [],
        array $columns = array('*'))
    {
        $query = $this->newQuery();

        if (empty($relations) === false)
        {
            $query->with($relations);
        }

        return $query->find($id, $columns);
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
        $dirty = $entity->getDirty();

        $entity->saveOrFail($options);

        $this->syncToEs($entity, EsRepository::UPSERT, $dirty);
    }

    public function deleteOrFail($entity)
    {
        parent::deleteOrFail($entity);

        $this->syncToEs($entity, EsRepository::DELETE);
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
     * @return
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

    /**
     * Sets $esRepo
     *
     * Needs to be called explicitly one time when intending to use. This cannot
     * be put in _construct of this class as it needs rzp.mode and that is not
     * set in few flows - tests etc.
     *
     * @return
     */
    public function setEsRepoIfExist()
    {
        $esRepoClassPath = $this->getEsRepoClassPath();

        if (class_exists($esRepoClassPath) === true)
        {
            $this->esRepo = (new $esRepoClassPath($this->entity));
        }
    }

    /**
     * Gets $esRepo
     *
     * @return EsRepository|null
     */
    public function getEsRepo()
    {
        return $this->esRepo;
    }

    /**
     * Find entity with given id for indexing.
     *
     * @param string $id
     *
     * @return array
     */
    public function findForIndexing(string $id): array
    {
        $query = $this->newQuery();

        $this->modifyQueryForIndexing($query);

        $entity = $query->find($id);

        return $this->serializeForIndexing($entity);
    }

    /**
     * Finds many entities for indexing.
     *
     * @param int|integer $skip
     * @param int|integer $take
     *
     * @return array
     */
    public function findManyForIndexing(int $skip = 0, int $take = 100): array
    {
        $query = $this->newQuery();

        $this->modifyQueryForIndexing($query);

        $collection = $query->skip($skip)->take($take)->get();

        return array_map(
            function ($v)
            {
                return $this->serializeForIndexing($v);
            },
            $collection->all());
    }

    /**
     * Updates the default query for getting models for indexing.
     * E.g. In case of merchant, it needs join with merchant_detail, etc.
     *
     * @param BuilderEx $query
     *
     * @return
     */
    protected function modifyQueryForIndexing(BuilderEx $query) {}

    /**
     * Serializes a given model for indexing.
     * Please override this per need to avoid unnecessary MySQL queries.
     *
     * @param Models\Base\PublicEntity $entity
     *
     * @return array
     */
    protected function serializeForIndexing(Models\Base\PublicEntity $entity): array
    {
        // We use setVisible to make only select attributes available after
        // toArray. The result from toArray is directly passed to es client for
        // indexing.

        return $entity->setVisible($this->getEsRepo()->getFields())->toArray();
    }

    /**
     * @deprecated
     *
     * Saves dirtied entities to es if few conditions met.
     *
     * @param Models\Base\PublicEntity $entity
     * @param array                    $dirty
     *
     * @return
     */
    protected function syncToEsDeprecated(Models\Base\PublicEntity $entity, array $dirty)
    {
        $esFields = $this->esRepo->getFields();

        if (empty(array_intersect(array_keys($dirty), $esFields)) === true)
        {
            return;
        }

        try
        {
            $esRepoClassPath = $this->getEsRepoClassPath();

            $esType = $this->getEsType();

            $queueData = [
                'es_type'           => $esType,
                'entity'            => $entity->toArray(),
                'mode'              => $this->app['rzp.mode'],
            ];

            $this->queue->push($esRepoClassPath.'@fireStoreEntity', $queueData);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::ES_SAVE_FAILED, $entity->toArray());
        }
    }

    /**
     * Syncs model changes to es.
     * Upserts in case of addition/updates and deletes es document otherwise.
     *
     * Has set of conditions:
     * - Only follows if there is corresponding EsRepository class for model and
     *   dirtied (in case of updates) fields are in list of indexed fields.
     *
     * @param Models\Base\PublicEntity $entity
     * @param string                   $action
     * @param array                    $dirty
     *
     * @return
     */
    protected function syncToEs(
        Models\Base\PublicEntity $entity,
        string $action,
        array $dirty = [])
    {
        $this->setEsRepoIfExist();

        if ($this->esRepo === null)
        {
            return;
        }

        // If entity is in old flow use the old method. To be removed later.
        if ($this->isEntityInOldEsFlow($entity->getEntity()) === true)
        {
            return $this->syncToEsDeprecated($entity, $dirty);
        }

        $esFields = $this->esRepo->getFields();

        // If no es fields set, just return.
        if (count($esFields) === 0)
        {
            return;
        }

        // If dirtied fields in case of update doesn't include any of the indexed
        // field lists, return.
        //
        // TODO: getDirty() doesn't handle related models update. Currently there
        // is no such use case but will come very soon. Handle the same then.
        if (($action === EsRepository::UPSERT) and
            (empty(array_intersect(array_keys($dirty), $esFields)) === true))
        {
            return;
        }

        try
        {
            $mode = $this->app['rzp.mode'];

            $job = (new EsSync(
                        $mode,
                        $action,
                        $entity->getEntity(),
                        $entity->getId()
                    ))->delay(self::ES_JOB_DELAY);

            $mock = Config::get('queue.mock');

            if ($mock === false)
            {
                $queue = Config::get('queue.sqs_es_sync');

                $job->onConnection('sqs_multi_default')->onQueue($queue);
            }

            $this->dispatch($job);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_SAVE_FAILED,
                [
                    'entity_id' => $entity->getId(),
                    'action'    => $action,
                ]
            );
        }
    }

    /**
     * @deprecated
     *
     * @return string
     */
    protected function getEsRepoClassPath(): string
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
     * @deprecated
     *
     * Override this method in entity/repository in case the type name is
     * different for that entity.
     *
     * @return string
     */
    protected function getEsType(): string
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
