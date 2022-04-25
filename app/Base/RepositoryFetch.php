<?php

namespace RZP\Base;

use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants\Es;
use RZP\Constants\Mode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Database\Connection;
use RZP\Constants\Environment;
use RZP\Constants\Entity as E;
use RZP\Models\Base\EsRepository;
use RZP\Models\Feature\Constants;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Base\Traits\Es\Hydrator as EsHydrator;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Trait RepositoryFetch
 *
 * @package RZP\Base
 *
 * @property Fetch $entityFetch
 */
trait RepositoryFetch
{
    use EsHydrator;

    /**
     * Until now query parameters were only expected in 'fetch'
     * routes (e.g. GET /invoices) and so we have validation
     * rule sets on it based on authentication type. But now
     * with new use cases (e.g. expands) we would need validations
     * on 'find' routes (eg. GET /invoices/{id}).
     *
     * Also observation is allowed 'find' parameters set is always
     * going to be subset of 'fetch' parameters set. Following is
     * a set of query parameters to be allowed in 'find' routes.
     * We use this to validate the query parameters in those routes.
     *
     * @var array
     */
    protected $findParamRuleKeys = [
        self::EXPAND,
        self::EXPAND . '.*',
        self::DELETED,
    ];

    protected $fetchParamRules = [
        self::FROM          => 'integer',
        self::TO            => 'integer',
        self::COUNT         => 'integer|min:1|max:',
        self::SKIP          => 'integer',

        //
        // Idea is, by default expand can be send in query for all current
        // fetch routes, similar to other common query parameter eg. skip etc.
        //
        // By default no value is allowed, one must specify the same(2nd line)
        // in respective repository branch. This is done to avoid unnecessary
        // exposing of relation attributes.
        //

        self::EXPAND        => 'sometimes|array|max:5',
        self::EXPAND . '.*' => 'string|in:',
    ];

    protected $defaultFetchParamRules;

    /**
     * Ids which have signed prefix.
     * We will need to remove the prefix before
     * they can be fetched.
     */
    // protected $signedIds = [];

      // Merchant allowed
//    protected $entityFetchParamRules = array();

      // Admin allowed
//    protected $appFetchParamRules = array();

      // Proxy allowed
//    protected $proxyFetchParamRules = array();

      // Default params
//    protected $defaultFetchParams = array();

    /**
     * Params for repository fetch
     *
     * @var array
     */
    protected $params      = [];

    /**
     * $params var gets split in $mysqlParams and $esParams which holds params to
     * be queried from MySQL and ES respectively.
     *
     * @var array
     */
    protected $mysqlParams = [];

    protected $baseQuery = null;

    /**
     * Holds params to be searched from ES.
     *
     * @var array
     */
    protected $esParams    = [];

    protected $merchantIdRequiredForMultipleFetch = true;

    public function fetchAndReturnPublicArrayWithExpand($id, $merchant, array $params)
    {
        return $this->findByPublicIdAndMerchant($id, $merchant, $params)->toArrayPublicWithExpand();
    }

    /**
     * Retrieves the entities according to given fetch params and current auth
     *
     *
     * @param array       $params
     * @param string|null $merchantId
     * @param string|null $connectionType
     * @return PublicCollection
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function fetch(array $params,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        // Process params (sanitization, validation, modification, etc.)
        $startTimeMs = round(microtime(true) * 1000);

        $this->processFetchParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $query = $this->newQuery();

        if ($this->baseQuery !== null)
        {
            $query = $this->baseQuery;
        }

        $connection = null;

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 500) {
            $this->trace->info(TraceCode::BUILD_QUERY_RESPONSE_DURATION, [
                'duration_ms' => $queryDuration,
            ]);
        }

        $startTimeMs = round(microtime(true) * 1000);

        if ((is_null($connectionType) === false) and
            ($this->app['env'] !== Environment::TESTING))
        {
            $connection = $this->getConnectionFromType($connectionType);

            $query = $this->newQueryWithConnection($connection);
        }

        $query = $query->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        $this->setEsRepoIfExist();

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 500) {
            $this->trace->info(TraceCode::REPLICA_LAG_RESPONSE_DURATION, [
                'duration_ms' => $queryDuration,
            ]);
        }
        $startTimeMs = round(microtime(true) * 1000);

        // Splits the params into mysqlParams and esParams. Check methods doc on
        // how that happens.
        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        // If we find that there are es params then we do es search.
        // Currently (as commented in getMysqlAndEsParams method) we raise bad
        // request error if we get mix of MySQL and es params. Later we might support
        // such thing.

        if (count($esParams) > 0)
        {
            $esSearchResult = $this->runEsFetch($esParams, $merchantId, $expands, $connectionType);

            $endTimeMs = round(microtime(true) * 1000);

            $queryDuration = $endTimeMs - $startTimeMs;

            if($queryDuration > 100) {
                $this->trace->info(TraceCode::ES_SEARCH_RESPONSE_DURATION, [
                    'duration_ms' => $queryDuration,
                ]);
            }

            return $esSearchResult;
        }

        $startTimeMs = round(microtime(true) * 1000);

        // If above doesn't happen we build query for mysql fetch and return the
        // result.
        $query = $this->buildFetchQuery($query, $mysqlParams);

        //
        // For now, we want to expose this only for proxy auth.
        // We would want to expose this to private auth as well
        // in the future, but need a little bit though around
        // how we want to expose it. Pagination has lot of standards
        // generally and we might want to follow those when
        // exposing on private auth. SDKs _might_ have to fixed too.
        //

        if ($this->auth->isProxyAuth() === true)
        {
            $paginatedResult = $this->getPaginated($query, $params);

            $endTimeMs = round(microtime(true) * 1000);

            $queryDuration = $endTimeMs - $startTimeMs;

            if($queryDuration > 100) {
                $this->trace->info(TraceCode::PAGINATED_RESPONSE_DURATION, [
                    'duration_ms' => $queryDuration,
                ]);
            }

            return $paginatedResult;
        }

        $startTimeMs = round(microtime(true) * 1000);

        $entities = $query->get();

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if ($queryDuration > 500)
        {
            $this->trace->info(TraceCode::DATA_WAREHOUSE_RESPONSE_DURATION, [
                'data_warehouse' => in_array($connection , Connection::DATA_WAREHOUSE_CONNECTIONS),
                'connection'     => $connection,
                'query_ctx'      => is_null($merchantId) ? 'admin' : 'merchant',
                'duration_ms'    => $queryDuration,
                'query'          => $query->toSql(),
                'merchantId'     => $merchantId,
            ]);
        }

        return $entities;
    }

    protected function getConnectionFromType(string $connection)
    {
        switch ($connection)
        {
            case ConnectionType::REPLICA:
                if ($this->app['api.route']->routeThroughMasterReplica())
                {
                    if ($this->useDataWarehouseConnection(Repository::ADMIN_FETCH) === true)
                    {
                       return $this->getDataWarehouseConnection();
                    }
                    return $this->getMasterReplicaConnection();
                }

            case ConnectionType::SLAVE:
                return $this->getSlaveConnection();

            case ConnectionType::DATA_WAREHOUSE_ADMIN:
                if ($this->isExperimentEnabled(self::ADMIN_TIDB_EXPERIMENT) === true)
                {
                    return $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);
                }

                return $this->getPaymentFetchReplicaConnection();

            case ConnectionType::DATA_WAREHOUSE_MERCHANT:
                if ($this->isExperimentEnabled(self::MERCHANT_TIDB_EXPERIMENT) === true)
                {
                    return $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);
                }

                return $this->getPaymentFetchReplicaConnection();

            case ConnectionType::RX_DATA_WAREHOUSE_MERCHANT:
                if ($this->isExperimentEnabled(Merchant\RazorxTreatment::RX_REARCH_TIDB_EXPERIMENT) === true)
                {
                    return $this->getDataWarehouseConnection(ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);
                }

                return $this->getPaymentFetchReplicaConnection();

            case ConnectionType::DATA_WAREHOUSE_ADMIN_REPLICA:
                if ($this->isExperimentEnabled(self::ADMIN_TIDB_EXPERIMENT) === true)
                {
                    return $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);
                }

                return $this->getMasterReplicaConnection();

            case ConnectionType::RX_ACCOUNT_STATEMENTS:
                return $this->getRxStatementConnection();
        }

        return $connection;
    }

    protected function isExperimentEnabled($experiment)
    {
        $app = $this->app;

        $variant = $app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(),
            $experiment, $app['basicauth']->getMode() ?? Mode::LIVE);

        $this->trace->info(TraceCode::REARCH_TIDB_EXPERIMENT_VARIANT, [
            'variant' => $variant,
            'experiment' => $experiment,
        ]);

        return ($variant === 'on');
    }

    protected function getPaginated(BuilderEx $query, array $params = [])
    {
        $this->resolvePageForPagination($query, $params);

        $paginatedResult = $query->simplePaginate();

        $hasMorePages = $paginatedResult->hasMorePages();

        $resultCollection = $paginatedResult->getCollection()
                                            ->setHasMore($hasMorePages);

        return $resultCollection;
    }

    /**
     * Returns [$mysqlParams, $esParams] pair. Only one of it would get used
     * in fetch() method.
     *
     * We find it with following simple logic:
     * - Most of the fields are queried from MySQL.
     * - There are few fields which can only be queried from ES e.g. notes.
     * - There are some fields which we index in ES just to assist with fetches
     *   for es only fields.
     *   E.g. we index invoice.type as well so that when notes
     *   is search along with type filter it works via ES. So all these fields will
     *   be in common. That means when just queried type, it'll not go to ES.
     *
     * @param array $params
     *
     * @return array
     * @throws BadRequestValidationFailureException
     */
    protected function getMysqlAndEsParams(array $params): array
    {
        if ($this->hasEntityFetch() === true)
        {
            return $this->entityFetch->groupMysqlAndEsParams($params);
        }

        if ($this->esRepo === null)
        {
            return [$params, []];
        }

        //
        // Following is list of keys common to Es & MySQL, only in ES, only in
        // MySQL respectively.
        // These do not include default keys(e.g. skip, count).
        //

        $commonFetchKeys = $this->esRepo->getCommonFetchParams();
        $esFetchKeys     = $this->esRepo->getEsFetchParams();
        $mysqlFetchKeys  = array_values(array_diff(
                                array_keys($this->fetchParamRules),
                                array_keys($this->defaultFetchParamRules),
                                $esFetchKeys,
                                $commonFetchKeys));

        //
        // Get input params which are not a part of the:
        // - Default param rules (see $fetchParamRules definition above), plus
        // - Common keys defined in EsRepo.
        //
        // The remainder/filtered list has to be a subset of either MySQL or ES keys
        // exclusively, otherwise an error will be raised. Hence both MySQL and
        // ES cannot be searched together in a single fetch operation.
        //

        $filteredParamsKeys = array_values(array_diff(
                                    array_keys($params),
                                    array_keys($this->defaultFetchParamRules),
                                    $commonFetchKeys));

        if (empty(array_diff($filteredParamsKeys, $mysqlFetchKeys)) === true)
        {
            // First value is MySQL params, so fetch will happen via MySQL
            return [$params, []];
        }
        else if (empty(array_diff($filteredParamsKeys, $esFetchKeys)) === true)
        {
            // Second value is Es params, so fetch will happen via Es
            return [[], $params];
        }
        else
        {
            $extraKeys = array_values(array_diff($filteredParamsKeys, $esFetchKeys));

            $message = implode(', ', $extraKeys) . ' not expected with other params sent';

            throw new BadRequestValidationFailureException(
                        $message,
                        null,
                        [
                            'params_keys'       => array_keys($params),
                            'common_fetch_keys' => $commonFetchKeys,
                            'es_fetch_keys'     => $esFetchKeys,
                            'mysql_fetch_keys'  => $mysqlFetchKeys,
                            'extra_keys'        => $extraKeys,
                        ]);
        }
    }

    /**
     * Runs ES fetch
     *
     * @param array       $params
     * @param string|null $merchantId
     *
     * @return PublicCollection
     */
    protected function runEsFetch(
        array $params,
        string $merchantId = null,
        array $expands,
        string $connectionType = null): PublicCollection
    {
        $startTimeMs = round(microtime(true) * 1000);
        $response = $this->esRepo->buildQueryAndSearch($params, $merchantId);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 100) {
            $this->trace->info(TraceCode::ES_SEARCH_DURATION, [
                'duration_ms' => $queryDuration,
                'function'    => 'runESSearch',
            ]);
        }

        // Extract results from ES response. If hit has _source get that else just the document id.
        $result = array_map(
                    function ($res)
                    {
                        return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
                    },
                    $response[ES::HITS][ES::HITS]);

        if (count($result) === 0)
        {
            return new PublicCollection;
        }

        // If callee expects only es data (for auto-complete etc) then hydrate the result into model and return.
        $esHitsOnly = boolval(($params[EsRepository::SEARCH_HITS]) ?? false);

        if ($esHitsOnly)
        {
            return $this->hydrate($result);
        }

        // Else extract the matched ids and return collection by making a MySQL query on found ids.
        $ids = array_column($result, 'id');
        // This is order of ids from es, sorted by _score first then created_at.
        $order = array_flip($ids);

        $query = $this->newQuery();

        if ((is_null($connectionType) === false) and
            ($this->app['env'] !== Environment::TESTING))
        {
            $connection = $this->getConnectionFromType($connectionType);

            $query = $this->newQueryWithConnection($connection);
        }

        $query = $query->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        $entities = $query
                         ->findMany($ids, ['*'])
                         // MySQL gives results in ascending order of id. Sorting again to keep correct ES's order.
                         ->sort(
                            function (PublicEntity $x, PublicEntity $y) use ($order)
                            {
                                return $order[$x->getId()] - $order[$y->getId()];
                            })
                         ->values();

        // If not all the ids from es are found in MySQL just log an error as this should not happen.
        if (count($ids) !== $entities->count())
        {
            $this->trace->critical(TraceCode::ES_MYSQL_RESULTS_MISMATCH, ['ids' => $ids]);
        }

        return $entities;
    }

    protected function buildQueryWithParams($query, $params)
    {
        foreach ($params as $key => $value)
        {
            $func = 'addQueryParam' . studly_case($key);

            if (method_exists($this, $func))
            {
                $this->$func($query, $params);
            }
            else
            {
                $this->addQueryParamDefault($query, $params, $key);
            }
        }
    }

    protected function buildFetchQuery($query, $params)
    {
        $this->buildQueryWithParams($query, $params);

        $this->addQueryOrder($query);

        $this->buildFetchQueryAdditional($params, $query);

        return $query;
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        return;
    }

    protected function modifyFetchParams(array & $params)
    {
        if (isset($this->signedIds) === false)
        {
            return;
        }

        $signedIds = array_flip($this->signedIds);

        $keys = array_keys(array_intersect_key($params, $signedIds));

        foreach ($keys as $key)
        {
            $entityKey = $key;

            // Remove '_id' prefix at end.
            if (substr($key, -3) === '_id')
            {
                $entityKey = substr($key, 0, -3);
            }

            // If not valid entity, then continue the loop
            if (E::isValidEntity($entityKey) === false)
            {
                continue;
            }

            // Gets entity class
            $entityClass = E::getEntityClass($entityKey);

            $value = $params[$key];

            if (($this->auth->isAdminAuth() === true) or
                ($this->auth->isPrivilegeAuth() === true))
            {
                // In case of admin auth, don't throw exception
                // if sign is not there
                $entityClass::verifyIdAndSilentlyStripSign($value);
            }
            else
            {
                $entityClass::verifyIdAndStripSign($value);
            }

            $params[$key] = $value;
        }
    }

    /**
     * Temporary:
     * There are clients(including Dashboard) which is sending
     * skip,count like extra parameters in GET routes. For now
     * everything which is not expected would be ignored. We'll
     * keep trace of violations and act on it later.
     *
     * @param array $params
     */
    protected function modifyFindParams(array $params): array
    {
        $filtered = array_only($params, $this->findParamRuleKeys);

        if (count($filtered) !== count($params))
        {
            $this->trace->info(TraceCode::EXTRA_QUERY_PARAM_IN_GET_ROUTE, $params);
        }

        return $filtered;
    }

    /**
     * Validates query parameters passed during GET by id endpoints.
     * E.g. GET /invoices/inv_123?expand[]=payments
     *
     * @param array $params
     */
    protected function validateFindParams(array $params)
    {
        $findParamRules = $this->getFindParamRulesForCurrentAuth();

        (new JitValidator)->rules($findParamRules)
                          ->caller($this)
                          ->input($params)
                          ->validate();
    }

    /**
     * Do a bunch of processing on the fetch param rules:
     * - Unset all the empty params (w/o any value)
     * - Add default params (internal [count] and user-defined)
     *   to the params list.
     * - Validate all the params basis auth as well ($appFetchParamRules,
     *   $proxyFetchParamRules, $adminFetchParamRules, etc.)
     *
     * @param  array $params Input params
     */
    protected function processFetchParams(array & $params)
    {
        if ($this->hasEntityFetch() === true)
        {
            return $this->entityFetch->processFetchParams($params);
        }

        $params = $this->unsetEmptyParams($params);

        $this->addDefaultParams($params);

        // validateFetchParams modifies fetchParamRules.
        // To check for ES fetch, we needs the original set of fetchParamRules (basically the default set)
        $this->defaultFetchParamRules = $this->fetchParamRules;

        $this->validateFetchParams($params);

        $this->modifyFetchParams($params);
    }

    /**
     * Returns the relations to be eager loaded in fetch/find query. It is list
     * of input expand(from query parameter) merged with the default list
     * defined in Repository.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getExpandsForQueryFromInput(array & $params): array
    {
        $extraExpands = $params[self::EXPAND] ?? [];

        unset($params[self::EXPAND]);

        return $this->getExpandsForQuery($extraExpands);
    }

    /**
     * Validates query parameters passed during GET endpoints.
     * E.g. GET /invoices?status=paid&expand[]=payments
     *
     * @param array $params
     */
    protected function validateFetchParams(array $params)
    {
        $this->fetchParamRules = $this->getFetchParamRulesForCurrentAuth();

        (new JitValidator)->rules($this->fetchParamRules)
                          ->caller($this)
                          ->input($params)
                          ->validate();

        $this->validateAdditional($params);
    }

    /**
     * Builds and returns rules to be used to validate query parameters
     * sent during get requests.
     *
     * @return array
     */
    protected function getFindParamRulesForCurrentAuth(): array
    {
        $fetchParamRules = $this->getFetchParamRulesForCurrentAuth();

        $rules = array_intersect_key(
                    $fetchParamRules,
                    array_flip($this->findParamRuleKeys));

        return $rules;
    }

    /**
     * Builds and returns rules to be used to validate query parameters
     * sent during fetch request.
     *
     * @return array
     */
    protected function getFetchParamRulesForCurrentAuth(): array
    {
        // Assign the default rules
        $rules = $this->fetchParamRules;

        // TODO: Check for uniqueness. Privileged auth should override proxy auth and so on.

        if (isset($this->entityFetchParamRules))
        {
            $rules = array_merge($rules, $this->entityFetchParamRules);
        }

        //
        // In case of privilege auth, we will merge proxyFetchParamRules
        // also here otherwise we won't be able to access those filters
        // in admin fetch
        //
        if (($this->auth->isProxyOrPrivilegeAuth()) and
            (isset($this->proxyFetchParamRules)))
        {
            $rules = array_merge($rules, $this->proxyFetchParamRules);
        }

        if (($this->auth->isPrivilegeAuth()) and
            (isset($this->appFetchParamRules)))
        {
            $rules = array_merge($rules, $this->appFetchParamRules);

            // Temporary fix to add deleted rule, Actual fix is done in Base/Fetch
            $rules[self::DELETED] = 'filled|string|in:0,1';
        }

        if (($this->auth->isAdminAuth()) and
            (isset($this->adminFetchParamRules)))
        {
            $rules = array_merge($rules, $this->adminFetchParamRules);
        }

        return $rules;
    }

    /**
     * Get rid of empty input params
     *
     * @param  array  $params   Input params
     * @return array            Sanitizied input params
     */
    protected function unsetEmptyParams(array $params): array
    {
        $newParams = [];

        foreach ($params as $key => $value)
        {
            if ($params[$key] !== '')
            {
                $newParams[$key] = $value;
            }
        }

        return $newParams;
    }

    protected function validateAdditional(array $params)
    {
        return;
    }

    /**
     * We need to do this because `simplePaginate` takes "page" instead of
     * using `skip` value directly. The formula used to convert page to skip
     * in simplePaginate function is "((page-1) * count)". We are just
     * doing inverse of that here.
     *
     * @param $query
     * @param $params
     */
    protected function resolvePageForPagination($query, $params)
    {
        $query->getModel()->setPerPage($params['count']);

        Paginator::currentPageResolver(function() use ($params) {
            $skip = $params['skip'] ?? 0;
            // We always add a default count param if not sent in the request.
            // Check `addDefaultParamCount` function.
            $count = $params['count'];

            return (($skip + $count) / $count);
        });
    }

    public function setMerchantIdRequiredForMultipleFetch($required)
    {
        $this->merchantIdRequiredForMultipleFetch = $required;
    }

    public function isMerchantIdRequiredForFetch()
    {
        if ($this->auth->isPrivilegeAuth() === true)
        {
             return false;
        }

        return $this->merchantIdRequiredForMultipleFetch;
    }

    public function findByPublicId($id)
    {
        $entity = $this->getEntityClass();

        $id = $entity::verifyIdAndStripSign($id);

        return $this->findOrFailPublic($id);
    }

    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): PublicEntity
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMerchant($id, $merchant, $params);
    }

    public function findManyByPublicIdsAndMerchant(
        array $ids,
        Merchant\Entity $merchant,
        array $params = []): PublicCollection
    {
        /** @var PublicEntity $entity */
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSignMultiple($ids);

        return $this->getQueryForFindWithParams($params)
                    ->merchantId($merchant->getId())
                    ->findManyOrFailPublic($ids);
    }

    /**
     * Finds entity against given id and merchant.
     *
     * @param string          $id
     * @param Merchant\Entity $merchant
     * @param array           $params
     *
     * @return PublicEntity
     */
    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): PublicEntity
    {
        if ($merchant->isFeatureEnabled(Constants::MERCHANT_ROUTE_WA_INFRA))
        {
            $query = $this->getQueryForFindWithParams($params, Connection::RX_WHATSAPP_LIVE);
        }
        else
        {
            $query = $this->getQueryForFindWithParams($params);
        }

        $entity = $query->merchantId($merchant->getId())
                        ->findOrFailPublic($id);

        //
        // Most of the entities can be filtered on Merchant ID. They have the
        // merchant() relation. But a few entities do not have this relation defined
        // and we have overridden scopeMerchantId() to filter on different column.
        // Eg: Merchant\Account\Entity applies the filter on column: parent_id.
        // Merchant\Account\Entity does not have merchant() relation defined. So skip it.
        //
        if (method_exists($entity, 'merchant') === true)
        {
            $entity->merchant()->associate($merchant);
        }

        return $entity;
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->findOrFailPublic($id);
    }

    /**
     * Along with Id, other allowed parameter can also be passed
     * Like: deleted
     *
     * @param string $id
     * @param array  $params
     * @param bool $useMasterEsReplica
     *
     * @return PublicEntity
     */
    public function findOrFailByPublicIdWithParams(
        string $id,
        array  $params,
        string $connectionType = null) : PublicEntity
    {
        $query = $this->getQueryForFindWithParams($params, $connectionType);

        $entity = $this->getEntityClass();

        $entity::silentlyStripSign($id);

        $entity = $query->findOrFailPublic($id);

        return $entity;
    }

    public function validateCustom($func, $attribute, $value, $parameters)
    {
        // Function name should start from 'validator'

        assertTrue (strpos($func, 'validate') === 0);

        $this->$func($attribute, $value, $parameters);
    }

    /**
     * Build query for find by id routes. In such routes expand[] or deleted
     * (for now) can be sent conditionally.
     *
     * @param array       $params
     * @param string|null $connectionType
     *
     * @return BuilderEx
     */
    protected function getQueryForFindWithParams(array $params, string $connectionType = null): BuilderEx
    {
        if ($this->hasEntityFetch() === true)
        {
            $this->entityFetch->processFindParams($params);
        }
        else
        {
            $params = $this->modifyFindParams($params);

            $this->validateFindParams($params);
        }

        $expands = $this->getExpandsForQueryFromInput($params);

        $query = $this->newQuery()->with($expands);

        if ((is_null($connectionType) === false) and
            ($this->app['env'] !== Environment::TESTING))
        {
            $query = $this->newQueryWithConnection($this->getConnectionFromType($connectionType))->with($expands);
        }

        $this->buildQueryWithParams($query, $params);

        return $query;
    }

    protected function addQueryParamDefault($query, $params, $key)
    {
        $attribute = $this->dbColumn($key);
        $value     = $params[$key];

        if ($value === 'null')
        {
            $query->whereNull($attribute);
        }
        else if ((is_array($value) === true) and (is_sequential_array($value) === true))
        {
            $query->whereIn($attribute, $value);
        }
        else
        {
            $query->where($attribute, $value);
        }
    }

    /**
     * In Fetch merchant_id can also be injected from code.
     * Method will add merchant id in the query even if it
     * is not part of input.
     *
     * @param BuilderEx $query
     * @param string    $merchantId
     */
    protected function addCommonQueryParamMerchantId($query, $merchantId)
    {
        // For admins, merchant ID may not be required.
        // For merchants, the ID is always required.

        if ($merchantId !== null)
        {
            $query = $query->merchantId($merchantId);
        }

        //
        // We need to check whether merchant id is required or not
        // to perform the query. This is important because when
        // merchant is making a query, it needs to be enforced
        // and should not be missing by mistake.
        //
        if ($this->isMerchantIdRequiredForFetch())
        {
            if ($merchantId === null)
            {
                throw new InvalidArgumentException('Merchant Id is required for fetch query');
            }
        }
    }

    protected function addQueryParamFrom($query, $params)
    {
        $createdAt = $this->dbColumn(Common::CREATED_AT);
        $query = $query->where($createdAt, '>=', $params['from']);
    }

    protected function addQueryParamTo($query, $params)
    {
        $createdAt = $this->dbColumn(Common::CREATED_AT);
        $query = $query->where($createdAt, '<=', $params['to']);
    }

    protected function addQueryOrder($query)
    {
        if (!in_array($query->getConnection()->getName(), Connection::DATA_WAREHOUSE_CONNECTIONS, true))
        {
            $query->orderBy($this->dbColumn(Common::CREATED_AT), 'desc');
        }

        $query->orderBy($this->dbColumn(Common::ID), 'desc');
    }

    protected function addQueryParamCount($query, $params)
    {
        $query->take($params['count']);
    }

    protected function addQueryParamSkip($query, $params)
    {
        $query->skip($params['skip']);
    }

    protected function addQueryParamDeleted($query, $param)
    {
        $deleted = (bool) $param[self::DELETED];

        if (($deleted === true) and ($this->doesEntityUseSoftdeletes() === true))
        {
            $query->withTrashed();
        }
    }

    protected function doesEntityUseSoftdeletes() : bool
    {
        $entity = $this->getEntityClass();

        return in_array(
            SoftDeletes::class,
            class_uses_recursive($entity),
            true);
    }
    /**
     * Add default params to the param list required
     * for fetch operation.
     *
     * @param array $params
     */
    protected function addDefaultParams(array & $params)
    {
        // Add `count`
        $this->addDefaultParamCount($params);

        // Add other default params
        if (isset($this->defaultFetchParams))
        {
            foreach ($this->defaultFetchParams as $key => $value)
            {
                $params[$key] = $value;
            }
        }
    }

    /**
     * Add merchant_id dynamically to query after verify
     * This way Entities only need to set access for it.
     *
     * Note: All the entities has set the rule to alpha_num.
     *       Rather than changing and forcing correct rule
     *       We are here validating before injecting in query.
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamMerchantId($query, $params)
    {
        Merchant\Entity::verifyIdAndStripSign($params[Common::MERCHANT_ID]);

        $query->merchantId($params[Common::MERCHANT_ID]);
    }

    /**
     * Add `count` param specifying number of
     * records to fetch.
     *
     * @param array $params
     */
    protected function addDefaultParamCount(array & $params)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            $max    = 1000;
            $count  = 1000;
        }
        else if ($this->auth->isPrivilegeAuth() === false)
        {
            $max    = 100;
            $count  = 10;
        }
        else
        {
            $max    = 1000;
            $count  = 1000;
        }

        // In case multiple assertions are checked with different auths in same testcase, the max value for count
        // needs to always replaced. Hence preg_replace is being used to achieve that.
        $this->fetchParamRules[self::COUNT] = preg_replace(
            '/max\:(\d)*/',
            sprintf('max:%d', $max),
            $this->fetchParamRules[self::COUNT]
        );

        if (isset($params['count']) === false)
        {
            $params['count'] = $count;
        }
    }

    public function verifyIdAndStripSign(string $id)
    {
        $entity = $this->getEntityClass();

        $id = $entity::verifyIdAndStripSign($id);

        return $id;
    }
}
