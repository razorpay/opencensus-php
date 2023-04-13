<?php

namespace RZP\Base;

use DB;
use App;
use Config;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

use RZP\Models;
use RZP\Exception;
use RZP\Jobs\EsSync;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Database\Connection;
use RZP\Http\RequestHeader;
use RZP\Constants\Entity as E;
use RZP\Constants\Environment;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\Collection;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\Entity as BaseEntity;
use RZP\Base\Database\ConnectionHeartbeatLagChecker;

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

    // Data Warehouse
    const ADMIN_FETCH                    = "data_warehouse_admin_fetch";
    const MERCHANT_FETCH                 = "data_warehouse_merchant_fetch";
    const MERCHANT_TIDB_EXPERIMENT       = 'rearch_fetch_tidb_or_slave'; // used as experiment for merchant tidb cluster
    const ADMIN_TIDB_EXPERIMENT          = 'admin_tidb_experiment';
    const TIDB_GATEWAY_FALLBACK          = 'tidb_gateway_fallback';
    const PAYMENT_QUERIES_TIDB_MIGRATION = 'payment_queries_tidb_migration';

    const ADMIN_TIDB_EXPERIMENT_REFUNDS = 'admin_tidb_experiment_refunds';

    const WDA_MIGRATION_ADMIN = 'wda_migration_admin';
    const WDA_PAYMENT_FETCH_MULTIPLE_MIGRATION = 'wda_payment_fetch_multiple_migration';

    protected $app;

    protected $db;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth $auth;
     */
    protected $auth;

    /**
     * Trace instance for tracing
     * @var $trace Trace
     */
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

    /**
     * @var RepositoryManager
     */
    public $repo;

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->auth = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->route = $this->app['api.route'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->entityFetch = E::getEntityFetch($this->entity);
    }

    public function setMerchant($merchant)
    {
        $this->merchant = $merchant;
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

    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->findOrFailPublic($id, $columns);
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

    public function findManyWithRelations($ids, $relations, $columns = array('*'), $useWarehouse = false)
    {
        if ($useWarehouse === false)
        {
            $query = $this->newQuery();
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT));;
        }

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

    public function findManyByMerchantIds(array $mids): Collection
    {
        return $this->newQuery()->whereIn(Common::MERCHANT_ID, $mids)->get();
    }

    /**
     * @throws \Throwable
     */
    public function existsInTable($tableName, $id, $throwException = false): bool
    {
        $model = null;

        try
        {
            $model = $this->newQuery()->from($tableName)->where(Common::ID, '=', $id)->first();
        }
        catch (\Throwable $exception)
        {
            if ($throwException === true)
            {
                throw $exception;
            }
        }

        return ($model !== null);
    }

    public function saveOrFail($entity, array $options = array())
    {
        $reInsertArchivedEntity = false;

        $originalTimeStampsValue = $entity->timestamps;

        if ((method_exists($entity, 'isArchived') === true) and
            ($entity->isArchived() === true))
        {
            $reInsertArchivedEntity = true;

            // on reinsert to DB, created_at should remain as the original timestamp and updated_at as the current timestamp
            $entity->timestamps = false;

            $entity->{BaseEntity::UPDATED_AT} = Carbon::now()->getTimestamp();
        }

        $this->saveOrFailImplementation($entity, $options, true);

        if ($reInsertArchivedEntity === true)
        {
            $entity->setArchived(false);

            $entity->timestamps = $originalTimeStampsValue;
        }
    }

    public function saveOrFailWithoutEsSync($entity, array $options = array())
    {
        $this->saveOrFailImplementation($entity, $options, false);
    }

    protected function saveOrFailImplementation($entity, array $options = array(), $esSyncFlag = true)
    {
        // TODO: getDirty() doesn't handle related models update. Currently there
        // is no such use case but will come very soon. Handle the same then.

        $dirty = $entity->getDirty();

        $action = $entity->exists ? EsRepository::UPDATE : EsRepository::CREATE;

        $this->updateIdempotencyTableIfRequired($entity);

        $entity->saveOrFail($options);

        if ($esSyncFlag === true)
        {
            $this->syncToEs($entity, $action, $dirty);
        }
    }

    protected function updateIdempotencyTableIfRequired(PublicEntity $entity)
    {
        // We create the idempotency entity if required and store
        // it always in the basicauth in the middleware itself.
        $idempotencyKeyId = $this->auth->getIdempotencyKeyId();

        $applicableSourceTypes = $this->route->getEntitiesForIdempotencyRequest();

        // Not checking for method_exists since `getMerchantId` is defined in `Base/PublicEntity`.
        // Hence, every entity will have this defined by default.
        $merchantId = $entity->getMerchantId();

        $routeSourceType = $this->route->getEntityForIdempotencyRequest();

        // This return statement has been added much later. Now, some of the following
        // return statements will be void. Redundant. Will remove them later as required.
        if ($routeSourceType !== $entity->getEntityName())
        {
            return;
        }

        // To avoid circular calls and we don't need to have idempotency on idempotency entity.
        if ($entity->getEntityName() === E::IDEMPOTENCY_KEY)
        {
            return;
        }

        // If there's no idempotency key sent in the header, no need to go further.
        if (empty($idempotencyKeyId) === true)
        {
            return;
        }

        if (in_array($entity->getEntityName(), $applicableSourceTypes, true) === false)
        {
            return;
        }

        // Idempotency flows are for entities with merchant IDs only since
        // this is specifically being built for merchants only.
        if (empty($merchantId) === true)
        {
            return;
        }

        $fetchInput = [
            // This is very important. This is required since we are calling this
            // function from `Base/Repository` and hence might end up saving the
            // wrong entity in the table. This can happen since we could be saving
            // multiple different types of entities in a single flow.
            Models\IdempotencyKey\Entity::SOURCE_TYPE   => $entity->getEntityName(),
            Models\IdempotencyKey\Entity::ID            => $idempotencyKeyId,
        ];

        /** @var Models\IdempotencyKey\Entity $idempotencyKeyEntity */
        $idempotencyKeyEntity = $this->repo->idempotency_key->fetch($fetchInput, $merchantId)->first();

        if (empty($idempotencyKeyEntity) === true)
        {
            // Logging this as an error since this situation should never come up ideally.
            // Not throwing an exception for now. Should throw an exception it in the future.
            $this->trace->error(
                TraceCode::IDEM_KEY_ENTITY_MISSING_FETCH,
                [
                    'entity_name'               => $entity->getEntityName(),
                    'entity_id'                 => $entity->getId(),
                    'idempotency_key_id'        => $idempotencyKeyId,
                    'applicable_source_types'   => $applicableSourceTypes,
                    'merchant_id'               => $merchantId,
                    'route_source_type'         => $routeSourceType,
                ]);

            return;
        }

        $idempotencyKeyEntity->source()->associate($entity);

        $this->repo->saveOrFail($idempotencyKeyEntity);

        $this->trace->info(
            TraceCode::IDEM_KEY_UPDATE_DATA,
            [
                'entity_id'             => $entity->getId(),
                'entity_type'           => $entity->getEntityName(),
                'merchant_id'           => $merchantId,
                'idempotency_key_id'    => $idempotencyKeyEntity->getId(),
                'idempotency_key'       => $idempotencyKeyEntity->getIdempotencyKey(),
            ]);
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

    /**
     * Checks whether the given ids for the entity exist in the database.
     *
     * @param  mixed $ids array of unsigned ids | entity collection | entity
     */
    public function validateExists($ids)
    {
        $parsedIds = $this->parseIds($ids);

        $expectedCount = count($parsedIds);

        $actualCount = $this->newQuery()
                            ->whereIn(
                                $this->getEntityObject()->getKeyName(),
                                $parsedIds)
                            ->count();

        if ($expectedCount !== $actualCount)
        {
            throw new Exception\RuntimeException('entity ids being attached do not exist', [
                'expected_count' => $expectedCount,
                'actual_count'   => $actualCount,
                'ids'            => $parsedIds
            ]);
        }
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

    /**
     * This gives the connection to slave with a feature of `lagThreshold`. If the current
     * lag is more than the threshold provided, we will fail the query immediately with a
     * ServerError. If no lagThreshold is provided, it will return back the slave connection
     * irrespective of what the current lag is.
     *
     * @param null $lagThreshold To be give in Milliseconds.
     *                           For example, 5 minutes lag threshold is 300000 milliseconds
     *
     * @return Builder
     * @throws Exception\ServerErrorException
     */
    public function newQueryOnSlave($lagThreshold = null)
    {
        $slaveConnection = $this->getSlaveConnection();

        $heartbeatConfig = $this->app['config']->get('database.connections.live.heartbeat_check');

        $heartbeatEnabled = $heartbeatConfig['enabled'] ?? true ;

        //If heartbeat check is false then we don't check the replication lag and directly make the query to slave db
        if ($heartbeatEnabled === false)
        {
            return $this->newQueryWithConnection($slaveConnection);
        }

        $replicationLagInMilli = $this->app['db.connector.mysql']->getReplicationLagInMilli($slaveConnection);

        if (($lagThreshold !== null) and
            ($replicationLagInMilli > $lagThreshold))
        {
            throw new Exception\ServerErrorException(
                'Replication lag greater than the defined threshold',
                ErrorCode::SERVER_ERROR_SLAVE_LAG_THRESHOLD_BREACHED,
                [
                    'lag_threshold'    => $lagThreshold,
                    'actual_lag_in_ms' => $replicationLagInMilli
                ]);
        }

        return $this->newQueryWithConnection($slaveConnection);
    }

    /**
     * This gives the connection to payment fetch Replica with a feature of `lagThreshold`.
     * If the current lag is more than the threshold provided, we will fail the query immediately with a
     * ServerError. If no lagThreshold is provided, it will return back the slave connection
     * irrespective of what the current lag is.
     *
     * @param null $lagThreshold To be give in Milliseconds.
     *                           For example, 5 minutes lag threshold is 300000 milliseconds
     *
     * @return Builder
     * @throws Exception\ServerErrorException
     */
    public function newQueryOnPaymentFetchReplica($lagThreshold = null)
    {
        $slaveConnection = $this->getPaymentFetchReplicaConnection();

        $heartbeatConfig = $this->app['config']->get('database.connections.live.heartbeat_check');

        $heartbeatEnabled = $heartbeatConfig['enabled'] ?? true ;

        //If heartbeat check is false then we don't check the replication lag and directly make the query to slave db
        if ($heartbeatEnabled === false)
        {
            return $this->newQueryWithConnection($slaveConnection);
        }

        $replicationLagInMilli = $this->app['db.connector.mysql']->getReplicationLagInMilli($slaveConnection);

        if (($lagThreshold !== null) and
            ($replicationLagInMilli > $lagThreshold))
        {
            throw new Exception\ServerErrorException(
                'Replication lag greater than the defined threshold',
                ErrorCode::SERVER_ERROR_SLAVE_LAG_THRESHOLD_BREACHED,
                [
                    'lag_threshold'    => $lagThreshold,
                    'actual_lag_in_ms' => $replicationLagInMilli
                ]);
        }

        return $this->newQueryWithConnection($slaveConnection);
    }

    public function find($id, $columns = array('*'), string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->find($id, $columns);
    }

    /**
     * Overwriting this here, as we want to reuse the find method overridden
     * in certain repository classes (required for query caching). We want to execute find and throw exception
     * if the entity is not found.
     *
     * TODO: Move this to spine
     * @param  string $id
     * @param  array  $columns
     * @return \RZP\Models\Base\Entity
     */
    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        if ( ! is_null($model = $this->find($id, $columns, $connectionType))) return $model;

        $this->processDbQueryFailure('find', array('id' => $id, 'columns' => $columns));
    }

    /**
     * Find the entity on master. We want to query master db for flows that are less tolerant to replica lag.
     *
     * @param string   $id
     * @param array $columns
     *
     * @return \RZP\Models\Base\Entity
     */
    public function findOrFailOnMaster(string $id, $columns = array('*'))
    {
        $mode = $this->app['rzp.mode'];

        if ( ! is_null($model = $this->newQueryWithConnection($mode)->useWritePdo()->find($id, $columns))) return $model;

        $this->processDbQueryFailure('find', array('id' => $id, 'columns' => $columns));
    }

    protected function parseIds($value): array
    {
        if ($value instanceof Model)
        {
            return [$value->getKey()];
        }

        if ($value instanceof Collection)
        {
            return $value->modelKeys();
        }

        return (array) $value;
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
        assertTrue ($this->isTransactionActive());
    }

    public function fetchBetweenTimestampWithRelations($merchantId, $from, $to, $count, $skip = 0, $relations = [], $useWarehouse = false)
    {
        $query = $this->getFetchBetweenTimestampQuery($merchantId, $from, $to, $useWarehouse);

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
     * @param PublicEntity $entity
     * @param bool|boolean $withTrashed
     */
    public function lockForUpdateAndReload(PublicEntity $entity, bool $withTrashed = false)
    {
        assertTrue($this->isTransactionActive(), 'Lock for update attempted without transaction!');

        $lockedEntity = $this->lockForUpdate($entity->getId(), $withTrashed);

        $entity->setRawAttributes($lockedEntity->getAttributes(), true);
    }

    /**
     * Fetches entity with given id with a MySQL lock for update
     *
     * @param string $id
     * @param bool   $withTrashed - Whether to include soft deleted results?
     *
     * @return PublicEntity
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

    protected function getFetchBetweenTimestampQuery($merchantId, $from, $to, $useWarehouse = false)
    {
        $query = null;

        if ($useWarehouse === false)
        {
            $query = $this->newQuery();
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT));;
        }

        return $query->betweenTime($from, $to)
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
     * Find entities with given ids for indexing.
     *
     * @param array $ids
     *
     * @return array
     */
    public function findManyForIndexingByIds(array $ids): array
    {
        $query = $this->newQuery();

        $this->modifyQueryForIndexing($query);

        $collection = $query->findOrFail($ids);

        return array_map(
            function($v)
            {
                return $this->serializeForIndexing($v);
            },
            $collection->all());
    }

    /**
     * Finds many entities for indexing.
     *
     * @param string|null $afterId
     * @param int         $take
     * @param int|null    $createdAtStart
     * @param int|null    $createdAtEnd
     *
     * @return array
     */
    public function findManyForIndexing(
        string $afterId = null,
        int $take = 100,
        int $createdAtStart = null,
        int $createdAtEnd = null): array
    {
        $query = $this->newQuery();

        $idCol        = $this->dbColumn(Common::ID);
        $createdAtCol = $this->dbColumn(Common::CREATED_AT);

        if ($afterId !== null)
        {
            $query->where($idCol, '>', $afterId);
        }

        if ($createdAtStart !== null)
        {
            $query->where($createdAtCol, '>=', $createdAtStart);
        }

        if ($createdAtEnd !== null)
        {
            $query->where($createdAtCol, '<=', $createdAtEnd);
        }

        $this->modifyQueryForIndexing($query);

        $collection = $query->take($take)->orderBy($idCol, 'asc')->get();

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
     * @param  PublicEntity $entity
     * @return array
     */
    protected function serializeForIndexing(PublicEntity $entity): array
    {
        // We use setVisible to make only select attributes available after
        // toArray. The result from toArray is directly passed to es client for
        // indexing.

        $fields = $this->esRepo->getIndexedFields();

        $serialized = $entity->setVisible($fields)->toArray();

        // Refer- config/es_mappings.php on how notes is indexed.
        if (array_key_exists(Common::NOTES, $serialized) === true)
        {
            $serialized[Common::NOTES] = array_map(
                function ($key, $value)
                {
                    return compact('key', 'value');
                },
                array_keys($serialized[Common::NOTES]),
                $serialized[Common::NOTES]
            );
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
     * @param PublicEntity $entity
     * @param string       $action
     * @param array        $dirty
     * @param string       $mode
     */
    public function syncToEs(
        PublicEntity $entity,
        string $action,
        array $dirty = null,
        string $mode = null)
    {
        $this->setEsRepoIfExist();

        if (($this->esRepo === null) or ($this->isEsSyncNeeded($action, $dirty, $entity) === false))
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
            // We do delayed dispatch here to account for time taken in db transaction(with numbers of queries) commit.
            EsSync::dispatch($mode, $action, $entity->getEntity(), $entity->getId())->delay(self::ES_JOB_DELAY);
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
        PublicEntity $entity,
        string $action,
        array $dirty = null)
    {
        $this->syncToEs($entity, $action, $dirty, Mode::LIVE);
        $this->syncToEs($entity, $action, $dirty, Mode::TEST);
    }

    /**
     * Checks if es sync after a model operation is needed or not.
     *
     * @param string            $action
     * @param array|null        $dirty
     * @param PublicEntity|null $entity
     *
     * @return bool
     */
    public function isEsSyncNeeded(string $action, array $dirty = null, PublicEntity $entity = null): bool
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

        return array_values(array_filter(array_unique($relations)));
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
        return (empty($this->entityFetch) === false);
    }

    protected function getMasterReplicaConnection(string $mode = null)
    {
        return $this->getSlaveConnection($mode);
    }

    protected function getArchivedDataReplicaConnection(string $mode = null)
    {
        if (($this->app->runningUnitTests() === true) or
            ($this->app['env'] === Environment::TESTING) or
            ($this->app['env'] === Environment::TESTING_DOCKER))
        {
            // Unit tests are written in such a way that data is deleted from test db and put in live db
            // So when running unit tests we are always returning live db connection.
            // Connection is LIVE and not ARCHIVED_DATA_REPLICA_LIVE because testing DBs are overriden in LIVE connection for unit testing
            return Connection::LIVE;
        }

        $mode = ($mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;

        $connection = ($mode === Mode::TEST) ? Connection::ARCHIVED_DATA_REPLICA_TEST : Connection::ARCHIVED_DATA_REPLICA_LIVE;

        return $connection;
    }

    protected function useDataWarehouseConnection(string $experiment): bool
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $connection = Connection::DATA_WAREHOUSE_LIVE;

        if ($mode !== Mode::LIVE)
        {
            $connection = Connection::DATA_WAREHOUSE_TEST;
        }

        $experimentResult = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), $experiment, $mode);

        if ($experimentResult === 'enable')
        {
            $isReplicationLag = (new ConnectionHeartbeatLagChecker($connection))->isConnectionLagging();

            return $isReplicationLag === false;
        }

        return false;
    }

    protected function getDataWarehouseConnection(string $cluster = null): string
    {
        if (in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER, Environment::BETA], true) === true)
        {
            return Config::get('database.default');
        }

        $mode = $mode ?? $this->app['rzp.mode'];

        // default connection is to Data warehouse live, can switch connection to different clusters as required.
        $connection = ($mode === Mode::TEST) ? Connection::SLAVE_TEST : Connection::DATA_WAREHOUSE_LIVE;

        if (is_null($cluster) === false)
        {
            if ($cluster === ConnectionType::DATA_WAREHOUSE_ADMIN)
            {
                $connection = ($mode === Mode::TEST) ? Connection::DATA_WAREHOUSE_ADMIN_TEST : Connection::DATA_WAREHOUSE_ADMIN_LIVE;
            }
            if ($cluster === ConnectionType::DATA_WAREHOUSE_MERCHANT)
            {
                $connection = ($mode === Mode::TEST) ? Connection::DATA_WAREHOUSE_MERCHANT_TEST : Connection::DATA_WAREHOUSE_MERCHANT_LIVE;
            }
            if ($cluster === ConnectionType::RX_DATA_WAREHOUSE_MERCHANT)
            {
                $connection = ($mode === Mode::TEST) ? Connection::DATA_WAREHOUSE_MERCHANT_TEST : Connection::DATA_WAREHOUSE_MERCHANT_LIVE;
            }
        }

        return $connection;
    }

    // Applied only on production env. As _record_source column is available only in the TiDB
    // All lower envs are pointed to RDS instance
    // Important Note : Use this only when _record_source filter needs to be applied.
    // As of now, using this connection applies the _record_source = 'api' filter on payments table only
    protected function getDataWarehouseSourceAPIConnection(string $cluster = ConnectionType::DATA_WAREHOUSE_ADMIN): string
    {
        if ((in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER], true) === true) or
            (Environment::isEnvironmentQA($this->app['env']) === true) or
            (Environment::isLowerEnvironment($this->app['env']) === true) or
            ($this->app->runningUnitTests() === true))
        {
            return Config::get('database.default');
        }

        $mode = $mode ?? $this->app['rzp.mode'];

        if ($mode === Mode::TEST)
        {
            // Ideal connection should be to data warehouse test, but that connection is broken on prod for some reason
            // To be fixed and used when the use case comes
            return Connection::TEST;
        }

        // This config key returns admin/merchant string values
        // Accordingly those clusters are assumed unhealthy and connection falls back to harvester replica
        // ToDo : Handle this fallback in an automated fashion with health check
        $badCluster = (string) ConfigKey::get(ConfigKey::DATA_WAREHOUSE_CONNECTION_FALLBACK, "");

        $connection = ($badCluster === 'admin') ?
            Connection::PAYMENT_FETCH_REPLICA_LIVE : Connection::DATA_WAREHOUSE_ADMIN_SOURCE_API_LIVE;

        if ($cluster === ConnectionType::DATA_WAREHOUSE_MERCHANT)
        {
            $connection = ($badCluster === 'merchant') ?
                Connection::PAYMENT_FETCH_REPLICA_LIVE : Connection::DATA_WAREHOUSE_MERCHANT_SOURCE_API_LIVE;
        }

        return $connection;
    }

    public function getSlaveConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'dev', 'testing_docker', 'beta'], true) === true)
        {
            return Config::get('database.default');
        }

        $mode = $mode ?? $this->app['rzp.mode'];

        $connection = ($mode === Mode::TEST) ? Connection::SLAVE_TEST : Connection::SLAVE_LIVE;

        return $connection;
    }

    public function getRxStatementConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            return Config::get('database.default');
        }

        return Connection::RX_ACCOUNT_STATEMENTS_LIVE;
    }

    public function getPayoutsServiceConnection(string $mode = null)
    {
        // For test cases we will use one of the live and test connection (which ever is free) of api as payout service db.
        // If test case is running on live mode then live connection will be API db and test connection will act as
        // payout service DB and vice versa.
        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            if ($this->app['rzp.mode'] === 'live')
            {
                return Connection::TEST;
            }

            return Connection::LIVE;
        }

        return Connection::PAYOUT_SERVICE_DATABASE;
    }

    public function getWhatsappDatabaseConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            return Config::get('database.default');
        }

        return Connection::RX_WHATSAPP_LIVE;
    }

    public function getWhatsappSlaveConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'dev', 'testing_docker'], true) === true)
        {
            return Config::get('database.default');
        }

        return Connection::RX_WHATSAPP_SLAVE_LIVE;
    }

    public function getReportingReplicaConnection(string $mode = null): string
    {
        return $this->getPaymentFetchReplicaConnection($mode);
    }

    public function getPaymentFetchReplicaConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'dev', 'testing_docker', 'beta'], true) === true)
        {
            return Config::get('database.default');
        }

        $mode = $mode ?? $this->app['rzp.mode'];

        $connection = ($mode === Mode::TEST) ? Connection::PAYMENT_FETCH_REPLICA_TEST : Connection::PAYMENT_FETCH_REPLICA_LIVE;

        return $connection;
    }

    public function getAccountServiceReplicaConnection(string $mode = null)
    {
        if (in_array($this->app['env'], ['testing', 'dev', 'testing_docker'], true) === true)
        {
            return Config::get('database.default');
        }

        return Connection::ACCOUNT_SERVICE_REPLICA_LIVE;
    }

    public function getUniqueMerchantIdsWhereBalanceIdIsNull(int $limit): array
    {
        assertTrue(
            in_array($this->entity, E::ENTITIES_WITH_BALANCE_ID_COLUMN),
            "Entity not whitelisted for this query - $this->entity");

        $mids = $this->newQuery()
                     ->select(PublicEntity::MERCHANT_ID)
                     ->whereNull('balance_id');

        if ($this->entity === E::TRANSACTION)
        {
            $mids->whereNotNull('balance');
        }

        return $mids->limit($limit)
                    ->distinct()
                    ->get()
                    ->pluck(PublicEntity::MERCHANT_ID)
                    ->toArray();
    }

    public function bulkUpdateBalanceId(string $merchantId, string $balanceId, int $limit)
    {
        assertTrue(
            in_array($this->entity, E::ENTITIES_WITH_BALANCE_ID_COLUMN),
            "Entity not whitelisted for this query - $this->entity");

        $data = $this->newQueryWithoutTimestamps()
                     ->where(PublicEntity::MERCHANT_ID, $merchantId)
                     ->whereNull('balance_id');

        if ($this->entity === E::TRANSACTION)
        {
            $data->whereNotNull('balance');
        }

        return $data->limit($limit)->update(['balance_id' => $balanceId]);
    }

    public function isExperimentEnabledForId(string $feature, string $id = null): bool
    {
        $app = $this->app;

        $contextId = $id ?? UniqueIdEntity::generateUniqueId();

        $variant = $app['razorx']->getTreatment($contextId, $feature, $app['basicauth']->getMode() ?? Mode::LIVE);

        $this->trace->info(TraceCode::ARCHIVAL_EXPERIMENTS_REPOSITORY_VARIANT, [
            'variant'    => $variant,
            'feature'    => $feature,
            'context_id' => $contextId,
        ]);

        return ($variant === 'on');
    }

    /**
     * IMP: This function is for specific use case of Account Service Data Migration
     * Please consider going through the implementation before using
     *
     * Returns the rows where updated_at is in the specified range
     *
     * @param int $from
     * @param int $to
     * @param int|null $limit
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function getIfUpdatedBetween(int $from, int $to, ?int $limit = null)
    {
        $data = $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->WhereBetween(PublicEntity::UPDATED_AT, [$from, $to]);

        if (isset($limit)) {
            $data->limit($limit);
        }

        return $data->get();
    }

    /**
     * mergeCollectionsBasedOnKey : Merges collection c2 into collection c1 based using $key in both collections items
     * as primary key. If a c2 item with same $key => $value is present in c1, c2 item will not be pushed during merge
     *
     * @param PublicCollection $c1
     * @param PublicCollection $c2
     * @param string $key
     * @return PublicCollection
     */
    public function mergeCollectionsBasedOnKey(PublicCollection $c1, PublicCollection $c2, string $key): PublicCollection
    {
        $combinedItems = $keyStore = [];

        foreach ($c1->toArrayWithItems()[PublicCollection::ITEMS] as $item)
        {
            $combinedItems[] = $item;

            if (empty($item[$key]) === false)
            {
                $keyStore[$item[$key]] = true;
            }
        }

        foreach ($c2->toArrayWithItems()[PublicCollection::ITEMS] as $item)
        {
            if (empty($keyStore[$item[$key]] ?? false) === true)
            {
                $combinedItems[] = $item;
            }
        }

        return new PublicCollection($combinedItems);
    }
}
