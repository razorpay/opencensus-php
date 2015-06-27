<?php

namespace Models\Base;

use EE\Exception;

trait RepositoryFetch
{
    protected $fetchParamRules = array(
        'from'          => 'integer',
        'to'            => 'integer',
        'count'         => 'integer|max:100|min:1',
        'skip'          => 'integer');

    protected $appFetchParamRules = array();

    protected $params = array();

    protected $merchantIdRequiredForMultipleFetch = true;

    /**
     * Retrieves the entities according to given fetch params
     * @params array        $params
     * @return Collection   A collection of entities
     */
    public function fetch(array $params, $merchantId = null)
    {
        if ($params === null)
        {
            throw new Exception\InvalidArgumentException('$params not provided');
        }

        $query = $this->newQuery();

        if ($this->isMerchantIdRequiredForFetch())
        {
            if ($merchantId === null)
            {
                throw new Exception\InvalidArgumentException(
                    'Merchant Id is required for fetch query');
            }

            $query = $query->where(Common::MERCHANT_ID, '=', $merchantId);
        }

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
        foreach ($this->fetchParamRules as $key => $value)
        {
            $func = 'addQueryParam'.$key;

            $this->$func($query, $params);
        }

        $this->addQueryOrder($query);

        $this->buildFetchQueryAdditional($params, $query);

        return $query;
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        return;
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Common::ID, 'desc');
    }

    protected function validateFetchParams(array $params)
    {
        if ($this->isAppAuth())
        {
            $this->fetchParamRules = array_merge($this->fetchParamRules, $this->appFetchParamRules);
        }

        validate($this->fetchParamRules, $params);

        $this->validateAdditional($params);
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
        if ($this->isAppAuth() === true)
            return false;

        return $this->merchantIdRequiredForMultipleFetch;
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        $repo = $this->repo;

        $query = $repo::where(Common::MERCHANT_ID, $merchantId);

        return $query->findOrFailPublic($id);
    }

    protected function addQueryParamFrom($query, $params)
    {
        if (empty($params['from']) === false)
        {
            $query = $query->where(Common::CREATED_AT, '>=', $params['from']);
        }
    }

    protected function addQueryParamTo($query, $params)
    {
        if (empty($params['to']) === false)
        {
            $query = $query->where(Common::CREATED_AT, '<=', $params['to']);
        }
    }

    protected function addQueryParamCount($query, $params)
    {
        $query->take($params['count']);
    }

    protected function addQueryParamSkip($query, $params)
    {
        if (empty($params['skip']) === false)
        {
            $query->skip($params['skip']);
        }
    }

    protected function addDefaultParams(array & $params)
    {
        if (empty($params['count']) === true)
        {
            $params['count'] = 10;
        }
    }


    public function isAppAuth()
    {
        return ($this->authType === 'app');
    }
}
