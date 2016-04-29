<?php

namespace Models\Base;

use EE\Exception;

trait RepositoryFetch
{
    protected $fetchParamRules = array(
        'from'          => 'integer',
        'to'            => 'integer',
        'count'         => 'integer|min:1',
        'skip'          => 'integer');

//    protected $entityFetchParamRules = array();

//    protected $appFetchParamRules = array();

//    protected $proxyFetchParamRules = array();

//    protected $defaultFetchParams = array();

    protected $params = array();

    protected $merchantIdRequiredForMultipleFetch = true;

    /**
     * Retrieves the entities according to given fetch params
     * @params array        $params
     * @return Collection   A collection of entities
     */
    public function fetch(array $params, $merchantId = null)
    {
        $params = $this->unsetEmptyParams($params);

        $query = $this->newQuery();

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        $this->addDefaultParams($params);

        $this->validateFetchParams($params);

        /*
         * Create the query.
         */
        $query = $this->buildFetchQuery($query, $params);

        return $query->get();
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

    public function findByIdAndMerchantId($id, $merchantId)
    {
        $repo = $this->repo;

        $mechantIdWithTable = $repo::getAttributeWithTableName(Common::MERCHANT_ID);
        $query = $repo::where($mechantIdWithTable, '=', $merchantId);

        return $query->findOrFailPublic($id);
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
        if ($merchantId !== null)
        {
            $query = $query->where(Common::MERCHANT_ID, '=', $merchantId);
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

    protected function addDefaultParamCount(array & $params)
    {
        $max = $count = null;

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
}
