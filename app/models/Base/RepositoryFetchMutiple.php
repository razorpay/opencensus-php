<?php

namespace Models\Base;

use EE\Exception;

trait RepositoryFetch
{
    protected static $fetchParamRules = array(
        'created'       => 'integer',
        'from'          => 'integer',
        'to'            => 'integer',
        'count'         => 'integer|max:100',
        'skip'          => 'integer');

    protected $params = array();

    protected $merchantIdRequiredForMultipleFetch = true;

    /**
     * Retrieves the entities according to given fetch params
     * @params array        $params
     * @return Collection   A collection of entities
     */
    public function fetch($params, $merchantId = null)
    {
        if ($params === null)
        {
            throw new Exception\InvalidArgumentException('$params not provided');
        }

        $this->validateFetchParams($params);

        /*
         * Create the query.
         */
        $query = $this->buildFetchQuery($params, $merchantId);

        if (($this->isMerchantIdRequiredForFetch()) and
            ($merchantId === null))
        {
            throw new Exception\InvalidArgumentException('Merchant Id is required for fetch query');
        }

        return $query->get();
    }

    protected function buildFetchQuery($params, $merchantId = null)
    {
        $repo = $this->repo;

        $query = (new $repo)->newQuery();

        if ($merchantId !== null)
        {
            $query = $query->where(Common::MERCHANT_ID, '=', $merchantId);
        }

        if (empty($params['from']) === false)
        {
            $query = $query->where(Common::CREATED_AT, '>=', $params['from']);
        }

        if (empty($params['to']) === false)
        {
            $query = $query->where(Common::CREATED_AT, '<=', $params['to']);
        }

        if (empty($params['count']) === false)
        {
            $query->take($params['count']);
        }
        else
        {
            $query->take(10);
        }

        if (empty($params['skip']) === false)
        {
            $query->skip($params['skip']);
        }

        $query->orderBy(Common::ID, 'desc');

        $this->buildFetchQueryAdditional($params, $query);

        return $query;
    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        return;
    }

    protected function validateFetchParams(array $params)
    {
        validate(self::$fetchParamRules, $params);

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
        return $this->merchantIdRequiredForMultipleFetch;
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        $repo = $this->repo;

        $query = $repo::where(Common::MERCHANT_ID, $merchantId);

        return $query->findOrFailPublic($id);
    }
}
