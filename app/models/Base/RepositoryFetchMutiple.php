<?php

namespace Models\Base;

use EE\Exception;

trait RepositoryFetchMultiple
{
    protected static $fetchParamRules = array(
        'created'       => 'numeric',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required');

    /**
     * Retrieves the entities according to given fetch params
     * @params array        $params
     * @return Collection   A collection of entities
     */
    public function fetch($params)
    {
        if ($params === null)
        {
            throw new Exception\InvalidArgumentException('$params not provided');
        }

        $this->validateFetchParams($params);

        $cols = array();

        /*
         * Create the query.
         */
        $repo = $this->repo;

        $query = $repo::where(Common::MERCHANT_ID, '=', $params['merchant_id']);

        if (isset($params['from']))
        {
            $query = $query->where(Common::CREATED_AT, '>=', $params['from']);
        }

        if (isset($params['to']))
        {
            $query = $query->where(Common::CREATED_AT, '<=', $params['to']);
        }

        if (isset($params['count']))
        {
            $query->take($params['count']);
        }
        else
        {
            $query->take(10);
        }

        if (isset($params['skip']))
        {
            $query->skip($params['skip']);
        }

        $query->orderBy(Common::ID, 'desc');

        return $query->get();
    }

    public function validateFetchParams(array $params)
    {
        validate(self::$fetchParamRules, $params);
    }
}
