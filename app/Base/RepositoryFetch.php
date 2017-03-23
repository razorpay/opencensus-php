<?php

namespace RZP\Base;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;

trait RepositoryFetch
{
    protected $fetchParamRules = array(
        'from'          => 'integer',
        'to'            => 'integer',
        'count'         => 'integer|min:1',
        'skip'          => 'integer');

    protected $originalFetchParamRules;

    //
    // TODO: Temporary: Will be removed once notes index is migrated to new flow.
    //
    protected $entitiesInOldFlow = [
        Constants\Entity::ORDER,
        Constants\Entity::PAYMENT,
        Constants\Entity::REFUND,
    ];

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

    protected $params      = [];
    protected $mysqlParams = [];
    protected $esParams    = [];

    protected $merchantIdRequiredForMultipleFetch = true;

    public function fetchAndReturnPublicArray($id, $merchant)
    {
        return $this->findByPublicIdAndMerchant($id, $merchant)->toArrayPublic();
    }

    /**
     * Retrieves the entities according to given fetch params
     *
     * @param array $params
     * @param $merchantId
     * @return Collection A collection of entities
     * @throws Exception\InvalidArgumentException
     */
    public function fetch(array $params, $merchantId = null)
    {
        $params = $this->unsetEmptyParams($params);

        $this->addDefaultParams($params);

        //
        // validateFetchParams modifies fetchParamRules.
        // To check for ES fetch, we needs the original set of fetchParamRules (basically the default set)
        //

        $this->originalFetchParamRules = $this->fetchParamRules;

        $this->validateFetchParams($params);

        // modify params if required
        $this->modifyFetchParams($params);

        $result = $this->runEsFetchIfNeededElseNull($params, $merchantId);

        if ($result !== null)
        {
            return $result;
        }

        //
        // Form MySQL query and do fetch
        //

        $query = $this->newQuery();

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        $query = $this->buildFetchQuery($query, $this->mysqlParams);

        return $query->get();
    }

    protected function runEsFetchIfNeededElseNull(array $params, string $merchantId = null)
    {
        $esRepo = $this->getEsRepoIfExistElseNull();

        if ($esRepo === null)
        {
            return null;
        }

        $this->esParams = array_intersect_key($params, array_flip($esRepo->getPossibleFieldsInParam()));

        $this->mysqlParams = array_diff_key($params, $this->esParams);

        if (count($this->esParams) === 0)
        {
            return null;
        }

        $mysqlParamsWithoutDefaults = array_diff_key($this->mysqlParams, $this->originalFetchParamRules);

        if (count($mysqlParamsWithoutDefaults) > 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                implode(', ', array_keys($mysqlParamsWithoutDefaults)) . ' not expected with other params sent');
        }

        $this->esParams += array_diff_key($params, $this->originalFetchParamRules);

        return $this->runEsFetch($esRepo, $params, $merchantId);
    }

    protected function runEsFetch($esRepo, $params, $merchantId)
    {
        $entity = $this->entity;

        if ($this->isEntityInOldEsFlow($entity) === true)
        {
            return $esRepo->fetch($params, $merchantId);
        }

        return $esRepo->buildQueryAndSearch($entity, $params, $merchantId);
    }

    protected function isEntityInOldEsFlow(string $entity)
    {
        return in_array($entity, $this->entitiesInOldFlow, true);
    }

    protected function buildFetchQuery($query, $params)
    {
        foreach ($params as $key => $value)
        {
            $func = 'addQueryParam'.studly_case($key);

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

    protected function validateFetchParams(array $params)
    {
        // TODO: Check for uniqueness. Privileged auth should override proxy auth and so on.

        if (isset($this->entityFetchParamRules))
        {
            $this->fetchParamRules = array_merge(
                $this->fetchParamRules, $this->entityFetchParamRules);
        }

        //
        // In case of privilege auth, we will merge proxyFetchParamRules
        // also here otherwise we won't be able to access those filters
        // in admin fetch
        //
        if (($this->auth->isProxyOrPrivilegeAuth()) and
            (isset($this->proxyFetchParamRules)))
        {
            $this->fetchParamRules = array_merge(
                    $this->fetchParamRules, $this->proxyFetchParamRules);
        }

        if (($this->auth->isPrivilegeAuth()) and
            (isset($this->appFetchParamRules)))
        {
            $this->fetchParamRules = array_merge(
                    $this->fetchParamRules, $this->appFetchParamRules);
        }

        if (($this->auth->isAdminAuth()) and
            (isset($this->adminFetchParamRules)))
        {
            $this->fetchParamRules = array_merge(
                    $this->fetchParamRules, $this->adminFetchParamRules);
        }

        (new JitValidator)->rules($this->fetchParamRules)
                          ->caller($this)
                          ->input($params)
                          ->validate();

        $this->validateAdditional($params);
    }

    protected function unsetEmptyParams(array $params)
    {
        $newParams = [];

        foreach ($params as $key => $value)
        {
            if (($params[$key]) !== '')
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

    public function findByPublicIdAndMerchant($id, $merchant)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMerchant($id, $merchant);
    }

    public function findByIdAndMerchant($id, Merchant\Entity $merchant)
    {
        $entity = $this->newQuery()
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
        if ($params[$key] === 'null')
        {
            $query->whereNull($key);
        }
        else
        {
            $query = $query->where($key, '=', $params[$key]);
        }
    }

    protected function addCommonQueryParamMerchantId($query, $merchantId)
    {
        // For admins, merchant ID may not be required.
        // For merchants, the ID is always required.

        if ($merchantId !== null)
        {
            $attr = static::getAttributeWithTableName(Common::MERCHANT_ID);
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
                throw new Exception\InvalidArgumentException(
                    'Merchant Id is required for fetch query');
            }
        }
    }

    protected function addQueryParamFrom($query, $params)
    {
        $createdAt = $this->getAttributeWithTableName(Common::CREATED_AT);
        $query = $query->where($createdAt, '>=', $params['from']);
    }

    protected function addQueryParamTo($query, $params)
    {
        $createdAt = $this->getAttributeWithTableName(Common::CREATED_AT);
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

    protected function addDefaultParams(array & $params)
    {
        $this->addDefaultParamCount($params);

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

    protected function addDefaultParamCount(array & $params)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            $max = 1000;
            $count = 1000;
        }
        else if ($this->auth->isPrivilegeAuth() === false)
        {
            $max = 100;
            $count = 10;
        }
        else
        {
            $max = 1000;
            $count = 1000;
        }

        $this->fetchParamRules['count'] .= '|max:'.$max;

        if (isset($params['count']) === false)
        {
            $params['count'] = $count;
        }
    }

    public function fetchAllNotesFromCreatedAt($skip, $createdAt, $count)
    {
        // Using created_at and not updated_at because updated_at is not indexed.
        return $this->newQuery()
                    ->select(Common::ID, 'notes', Common::MERCHANT_ID, Common::CREATED_AT)
                    ->where(Common::CREATED_AT, '>=', $createdAt)
                    ->orderBy(Common::ID, 'desc')
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }
}
