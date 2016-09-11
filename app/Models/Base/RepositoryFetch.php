<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Customer;

trait RepositoryFetch
{
    protected $fetchParamRules = array(
        'from'          => 'integer',
        'to'            => 'integer',
        'count'         => 'integer|min:1',
        'skip'          => 'integer');

    protected $originalFetchParamRules;

      // Merchant allowed
//    protected $entityFetchParamRules = array();

      // Admin allowed
//    protected $appFetchParamRules = array();

      // Proxy allowed
//    protected $proxyFetchParamRules = array();

      // Default params
//    protected $defaultFetchParams = array();

    protected $params = array();

    protected $merchantIdRequiredForMultipleFetch = true;

    public function fetchAndReturnPublicArray($id, $merchant)
    {
        return $this->findByPublicIdAndMerchant($id, $merchant)->toArrayPublic();
    }

    /**
     * Retrieves the entities according to given fetch params
     * @params array        $params
     * @return Collection   A collection of entities
     */
    public function fetch(array $params, $merchantId = null)
    {
        // In case there are keys, but no values in the query params.
        $params = $this->unsetEmptyParams($params);

        $query = $this->newQuery();

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        // In case some params like count are not mentioned in the query params.
        $this->addDefaultParams($params);

        // validateFetchParams modifies fetchParamRules.
        // To check for ES fetch, we needs the original set of fetchParamRules (basically the default set)
        $this->originalFetchParamRules = $this->fetchParamRules;

        // Validate the rules against each query param.
        $this->validateFetchParams($params);

        // Check if the params need to be searched via ES.
        $isEs = $this->isEsFetch($params);

        if ($isEs === true)
        {
            return $this->runEsFetch($params, $merchantId);
        }

        /*
         * Create the query.
         */
        $query = $this->buildFetchQuery($query, $params);

        return $query->get();
    }


    /*
     * Returns `false` if esWhitelistedParams are not set for the entity.
     * If default params such as 'from', 'to', 'skip' are present, it removes
     * them before checking. It also removes default params set for the entity, before checking.
     * If the remaining params, after removing the default params, are present in esWhitelistedParams,
     * ES Fetch is used.
     * Example : If query params contain notes and count, ES fetch is used, since count is part of default param
     * and is hence removed.
     * If query params contain notes and contact, ES fetch is not used, since contact is not part of either
     * default param or esWhitelistedParams. For ES fetch to be used, all the params remaining after removing
     * default params should be part of esWhitelistedParams.
     * If query params contain status, ES fetch is not used, since it's not part of esWhitelistedParams.
     * If after removing the default params, no params are left, ES fetch is not used.
     */
    protected function isEsFetch($params)
    {
        // Checks if esWhitelistedParams has been set for the entity.
        if (isset($this->esWhitelistedParams) === false)
        {
            return false;
        }

        // Gets the query param list without the default params
        $rawParams = array_diff_key($params, $this->originalFetchParamRules);

        if (isset($this->defaultFetchParams) === true)
        {
            // array_flip is not required here since defaultFetchparams will be an associative array.
            // array_diff_key is used when only the key needs to be considered and not the value.
            $rawParams = array_diff_key($rawParams, $this->defaultFetchParams);
        }

        // If there are no raw query params, don't do ES search.
        if (empty($rawParams) === true)
        {
            return false;
        }

        // Checks if the raw query params are present in the esWhitelistedParams list.
        // ($params - $esWhitelistedParams) should be 0.
        // Currently, not supporting ES+MySQL search through query params.
        if (empty(array_diff_key($rawParams, array_flip($this->esWhitelistedParams))) === true)
        {
            return true;
        }

        return false;
    }

    protected function runEsFetch($params, $merchantId)
    {
        $esRepo = $this->getEsRepoClass();

        return $esRepo->fetch($params, $merchantId);
    }

    protected function getEsRepoClass()
    {
        $esRepoClassPath = join('\\', explode('\\', get_called_class(), -1)) . '\\' . 'EsRepository';
        $esRepo = new $esRepoClassPath;

        return $esRepo;
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

    protected function validateFetchParams(array $params)
    {
        // TODO: Check for uniqueness. Privileged auth should override proxy auth and so on.

        if (isset($this->entityFetchParamRules))
        {
            $this->fetchParamRules = array_merge(
                $this->fetchParamRules, $this->entityFetchParamRules);
        }

        if (($this->auth->isProxyAuth()) and
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

        validate($this->fetchParamRules, $params);

        $this->validateAdditional($params);
    }

    protected function customEsValidations($params)
    {
        $esRepo = new $this->getEsRepoClass();
        foreach ($params as $key => $value)
        {
            $func = 'validateParam'.studly_case($key);

            if (method_exists($esRepo, $func))
            {
                $esRepo->$func([$key => $value]);
            }
        }
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

    protected function validateAdditional($params)
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

        $id = $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndMerchant($id, $merchant);
    }

    public function findByIdAndMerchant($id, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->findOrFailPublic($id);
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->findOrFailPublic($id);
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
        $repo = $this->repo;

        $createdAt = $repo::getAttributeWithTableName(Common::CREATED_AT);
        $query = $query->where($createdAt, '>=', $params['from']);
    }

    protected function addQueryParamTo($query, $params)
    {
        $repo = $this->repo;

        $createdAt = $repo::getAttributeWithTableName(Common::CREATED_AT);
        $query = $query->where($createdAt, '<=', $params['to']);
    }

    protected function addQueryParamEmail($query, $params)
    {
        $repo = $this->repo;

        $attribute = $repo::getAttributeWithTableName(Common::EMAIL);

        // Email should be case insensitive
        $email = mb_strtolower($params['email']);

        $query = $query->where($attribute, '=', $email);
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
        if ($this->auth->isPrivilegeAuth() === false)
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
                    ->select('id', 'notes', 'merchant_id', 'created_at')
                    ->where(PublicEntity::CREATED_AT, '>=', $createdAt)
                    ->orderBy('id', 'desc')
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }

    protected function getAttributeWithTableName($col)
    {
        $repo = $this->repo;

        return $repo::getTableName() . '.' . $col;
    }
}
