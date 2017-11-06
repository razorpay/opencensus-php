<?php

namespace RZP\Base;

use DB;
use Illuminate\Support\Facades\App;

use RZP\Models;
use RZP\Exception;
use RZP\Jobs\EsSync;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Jobs\DispatchRouter;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\EsRepository;
use Razorpay\Trace\Logger as Trace;

class Repository extends \Razorpay\Spine\Repository
{
    use RepositoryFetch;

    /**
     * Delay in making es job available for queue consumer.
     * Value is in seconds.
     *
     * We need delay to not fall in a case where a set of saveOrFail() are
     * wrapped in a transaction and the transaction is taking time. Meanwhile
     * saveOrFail() has triggered es sync via queue and queue receives the job
     * and attempts to get the entity by id(which is not committed yet, transaction
     * in progress).
     */
    const ES_JOB_DELAY = 3;

    /**
     * Query parameter: Holds list of relations to be
     * eager loaded when doing getting entity(s).
     *
     */
    const EXPAND       = 'expand';

    // Other common query parameters

    const FROM         = 'from';
    const TO           = 'to';
    const COUNT        = 'count';
    const SKIP         = 'skip';
    const DELETED      = 'deleted';

    protected $app;

    protected $db;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth $auth;
     */
    protected $auth;

    protected $trace;

    protected $manager;

    /**
     * @var null|Fetch
     */
    protected $entityFetch;

    /**
     * List of relations to be eager loaded when entity(s) is fetched via GET,
     * used in RepositoryFetch's methods.
     *
     * @var array
     */
    protected $expands = [];

    /**
     * Corresponding esRepo instance of entity.
     * When intending to use please set it first by calling setEsRepoIfExist().
     *
     * @var EsRepository
     */
    protected $esRepo = null;

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->auth = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->entityFetch = E::getEntityFetch($this->entity);
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
        // TODO: getDirty() doesn't handle related models update. Currently there
        // is no such use case but will come very soon. Handle the same then.

        $dirty = $entity->getDirty();

        $action = $entity->exists ? EsRepository::UPDATE : EsRepository::CREATE;

        $entity->saveOrFail($options);

        $this->syncToEs($entity, $action, $dirty);
    }

    public function deleteOrFail($entity)
    {
        parent::deleteOrFail($entity);

        $this->syncToEs($entity, EsRepository::DELETE);
    }

    /**
     * If detaching is true then all the previous relations for this entity would be removed,
     * and fresh new relations will be created.
     * If detaching is false, then it will not remove the previous relations
     * and will update the given relation.
     */
    public function sync($entity, $relation, $ids = [], bool $detaching = true)
    {
        $entity->$relation()->sync($ids, $detaching);

        $this->syncToEs($entity, EsRepository::UPDATE);

        return $this;
    }

    public function detach($entity, $relation, $ids = [])
    {
        $entity->$relation()->detach($ids);

        return $this;
    }

    public function attach(
        $entity,
        $relation,
        array $ids = [],
        array $attributes = [],
        $touch = true)
    {
        $entity->$relation()->attach($ids, $attributes, $touch);

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

    public function assertTransactionActive()
    {
        assert ($this->isTransactionActive());
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
            $typeEntities = $this->repo->$type->findMany($ids);

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
     */
    public function setEsRepoIfExist()
    {
        $parentNamespace = $this->getParentNamespace();

        $esRepoClassPath = $parentNamespace . '\\' . 'EsRepository';

        if (class_exists($esRepoClassPath) === true)
        {
            $this->esRepo = (new $esRepoClassPath($this->entity));
        }
    }

    public function setAndGetEsRepoIfExist()
    {
        $this->setEsRepoIfExist();

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

        $entity = $query->findOrFail($id);

        return $this->serializeForIndexing($entity);
    }

    /**
     * Finds many entities for indexing.
     *
     * @param int      $skip
     * @param int      $take
     * @param int|null $createdAtStart
     * @param int|null $createdAtEnd
     *
     * @return array
     */
    public function findManyForIndexing(
        int $skip = 0,
        int $take = 100,
        int $createdAtStart = null,
        int $createdAtEnd = null): array
    {
        $query = $this->newQuery();

        $idCol        = $this->dbColumn(Common::ID);
        $createdAtCol = $this->dbColumn(Common::CREATED_AT);

        if ($createdAtStart !== null)
        {
            $query->where($createdAtCol, '>=', $createdAtStart);
        }

        if ($createdAtEnd !== null)
        {
            $query->where($createdAtCol, '<=', $createdAtEnd);
        }

        $this->modifyQueryForIndexing($query);

        $collection = $query->skip($skip)
                            ->take($take)
                            ->orderBy($idCol, 'desc')
                            ->get();

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
    protected function modifyQueryForIndexing(BuilderEx $query)
    {
        //
    }

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

        $fields = $this->esRepo->getIndexedFields();

        $serialized = $entity->setVisible($fields)->toArray();

        // There is issue around Notes and NotesTrait which needs to be handled
        // there. For now following is the quickest solution to handle it.
        // Ref: https://github.com/razorpay/api/issues/1678

        if (array_key_exists(Common::NOTES, $serialized) === true)
        {
            $serialized[Common::NOTES] = (object) $serialized[Common::NOTES];
        }

        return $serialized;
    }

    /**
     * Syncs model changes to es.
     * Upserts in case of addition/updates and deletes es document otherwise.
     *
     * - $dirty: If dirty is not null then this will be used to check
     *           if es sync is required.
     *
     * - $mode:  If mode is passed then this will be used, else rzp.mode
     *           will be used.
     *
     * @param Models\Base\PublicEntity $entity
     * @param string                   $action
     * @param array                    $dirty
     * @param string                   $mode
     */
    public function syncToEs(
        Models\Base\PublicEntity $entity,
        string $action,
        array $dirty = null,
        string $mode = null)
    {
        $this->setEsRepoIfExist();

        if (($this->esRepo === null) or ($this->isEsSyncNeeded($action, $dirty) === false))
        {
            return;
        }

        // If $mode is provided use that else default to set rzp.mode
        $mode = $mode ?: $this->app['rzp.mode'];

        $tracePayload = [
            'action'    => $action,
            'entity'    => $entity->getEntity(),
            'entity_id' => $entity->getId(),
            'mode'      => $mode,
        ];

        try
        {
            $this->trace->debug(TraceCode::ES_SYNC_PUSH_PAYLOAD, $tracePayload);

            $job = (new EsSync(
                        $mode,
                        $action,
                        $entity->getEntity(),
                        $entity->getId()
                    ))->delay(self::ES_JOB_DELAY);

            (new DispatchRouter)->dispatchOn($job, DispatchRouter::ES_V2);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_SYNC_PUSH_FAILED,
                $tracePayload);
        }
    }

    public function syncToEsLiveAndTest(
        Models\Base\PublicEntity $entity,
        string $action,
        array $dirty = null)
    {
        $this->syncToEs($entity, $action, $dirty, Mode::LIVE);
        $this->syncToEs($entity, $action, $dirty, Mode::TEST);
    }

    /**
     * Checks if es sync after a model operation is needed or not.
     *
     * @param string $action
     * @param array  $dirty
     *
     * @return bool
     */
    public function isEsSyncNeeded(string $action, array $dirty = null): bool
    {
        $esFields = $this->esRepo->getIndexedFields();

        // Fields merchant_id and created_at never comes in dirty
        // as they are not update-able. But keeping this filter here
        // so during first time indexing these documents are not picked for
        // indexing as they have nothing search-able.

        $esFields = array_diff($esFields, [Common::ID, Common::MERCHANT_ID, Common::CREATED_AT]);

        // If no fields are configured to be in ES in the repository, return false.
        if (count($esFields) === 0)
        {
            return false;
        }

        // If $dirty is null, i.e. we don't have to do dirty check.
        if ($dirty === null)
        {
            return true;
        }

        // Checks if dirtied field($dirty) contains any of $esFields. If so, checks
        // if they are non-empty. Eg. '{}'' json string in notes doesn't need to
        // be indexed alone.

        if (empty(array_intersect(array_keys($dirty), $esFields)) === true)
        {
            return false;
        }

        // If it's update action and there is something dirtied, just sync.
        if ($action === EsRepository::UPDATE)
        {
            return true;
        }

        //
        // Otherwise if it's insert action then need to check if there is at least
        // one value in $dirty that IS set(not null values, e.g null, [], {} etc.).
        //
        $shouldSync = false;

        foreach ($esFields as $esField)
        {
            if (empty($dirty[$esField]) === false)
            {
                if (isJson($dirty[$esField]) === true)
                {
                    if (empty(json_decode($dirty[$esField], true)) === false)
                    {
                        $shouldSync = true;
                        break;
                    }
                }
                else
                {
                    $shouldSync = true;
                    break;
                }
            }
        }

        return $shouldSync;
    }

    public function getExpands(): array
    {
        return $this->expands;
    }

    /**
     * Returns an array which can be used in with() of BuilderEx.
     *
     * It camel cases $expands (which is generally the snake cased output key)
     * and returns unique list of it.
     *
     * @param array $extra - Optional, if provided returns list merged with default.
     *
     * @return array
     */
    public function getExpandsForQuery(array $extra = []): array
    {
        $defaultExpands = $this->expands;

        $expands = array_merge($defaultExpands, $extra);

        $relations = camel_case_array($expands);

        return array_values(array_unique($relations));
    }

    /**
     * Loads the relations as specified in $expands parameter.
     * This method will not unset existing loaded relations.
     *
     * @param PublicEntity $entity
     *
     * @return PublicEntity
     */
    public function loadRelations(PublicEntity $entity): PublicEntity
    {
        $relations = $this->getExpandsForQuery();

        $entity->load($relations);

        return $entity;
    }

    protected function getParentNamespace()
    {
        // get_called_class gives the (namespace+classname)
        // removing the last element to get only the namespace.
        return join('\\', explode('\\', get_called_class(), -1));
    }

    protected function dbColumn($col)
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

    protected function hasEntityFetch(): bool
    {
        return ((empty($this->entityFetch) === false) and ($this->entityFetch->isEnabled() === true));
    }
}
