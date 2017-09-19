<?php

namespace RZP\Base;

use RZP\Constants;
use RZP\Constants\Es;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Base\Traits\Es\Hydrator as EsHydrator;
use RZP\Exception\BadRequestValidationFailureException;

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
    ];

    protected $fetchParamRules = [
        self::FROM          => 'integer',
        self::TO            => 'integer',
        self::COUNT         => 'integer|min:1',
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

    /**
     * Holds params to be searched from ES.
     *
     * @var array
     */
    protected $esParams    = [];

    protected $merchantIdRequiredForMultipleFetch = true;

    public function fetchAndReturnPublicArray($id, $merchant)
    {
        return $this->findByPublicIdAndMerchant($id, $merchant)->toArrayPublic();
    }

    /**
     * Retrieves the entities according to given fetch params
     *
     * @param array       $params
     * @param string|null $merchantId
     *
     * @return PublicCollection
     * @throws InvalidArgumentException
     */
    public function fetch(array $params, string $merchantId = null): PublicCollection
    {
        // Process params (sanitization, validation, modification, etc.)
        $this->processFetchParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $query = $this->newQuery()->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        // Splits the params into mysqlParams and esParams. Check methods doc on
        // how that happens.
        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        // If we find that there are es params then we do es search.
        // Currently (as commented in getMysqlAndEsParams method) we raise bad
        // request error if we get mix of MySQL and es params. Later we might support
        // such thing.
        if (count($esParams) > 0)
        {
            return $this->runEsFetch($esParams, $merchantId, $expands);
        }

        // If above doesn't happen we build query for mysql fetch and return the
        // result.
        $query = $this->buildFetchQuery($query, $mysqlParams);

        return $query->get();
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
        $this->setEsRepoIfExist();

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
        array $expands): PublicCollection
    {
        $response = $this->esRepo->buildQueryAndSearch($params, $merchantId);

        //
        // Extract results from ES response: If hit has _source get that else
        // just the document id.
        //
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

        //
        // If callee expects only es data (no mysql queries) then hydrate
        // the es array result into model and return the collection.
        //
        $esHitsOnly = boolval(($params[EsRepository::SEARCH_HITS]) ?? false);

        if ($esHitsOnly)
        {
            return $this->hydrate($result);
        }

        //
        // Else extract the matched ids and return collection by making a mysql
        // query on found ids.
        //
        $ids = array_column($result, 'id');

        $entities = $this->newQuery()
                         ->with($expands)
                         ->findMany($ids, ['*']);

        // If the not all the ids from es are found in MySQL, just raise an error.
        if (count($ids) !== $entities->count())
        {
            $this->trace->critical(TraceCode::ES_MYSQL_RESULTS_MISMATCH, ['ids' => $ids]);
        }

        return $entities;
    }

    protected function buildFetchQuery($query, $params)
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
        ;
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

    /**
     * Finds entity against given id and merchant.
     *
     * @param string          $id
     * @param Merchant\Entity $merchant
     * @param array           $params
     *
     * @return mixed
     */
    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): PublicEntity
    {
        $params = $this->modifyFindParams($params);

        $this->validateFindParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $entity = $this->newQuery()
                       ->with($expands)
                       ->merchantId($merchant->getId())
                       ->findOrFailPublic($id);

        $entity->merchant()->associate($merchant);

        return $entity;
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->findOrFailPublic($id);
    }

    public function validateCustom($func, $attribute, $value, $parameters)
    {
        // Function name should start from 'validator'

        assert (strpos($func, 'validate') === 0);

        $this->$func($attribute, $value, $parameters);
    }

    protected function addQueryParamDefault($query, $params, $key)
    {
        $attribute = $this->dbColumn($key);
        $value     = $params[$key];

        if ($value === 'null')
        {
            $query->whereNull($attribute);
        }
        else
        {
            $query->where($attribute, $value);
        }
    }

    /**
     * Filter fetch operation by merchantId. Super important for
     * private auth calls.
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
            $attr = static::dbColumn(Common::MERCHANT_ID);
            $query = $query->where($attr, '=', $merchantId);
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
        $query->orderBy(Common::ID, 'desc');
    }

    protected function addQueryParamCount($query, $params)
    {
        $query->take($params['count']);
    }

    protected function addQueryParamSkip($query, $params)
    {
        $query->skip($params['skip']);
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

    protected function addQueryParamMerchantId($query, $params)
    {
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

        $this->fetchParamRules['count'] .= '|max:'.$max;

        if (isset($params['count']) === false)
        {
            $params['count'] = $count;
        }
    }
}
