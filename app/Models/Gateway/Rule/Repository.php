<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_rule';

    protected $entityFetchParamRules = [
        Entity::GATEWAY          => 'sometimes|string|max:25',
        Entity::MERCHANT_ID      => 'sometimes|alpha_num|size:14',
        Entity::TYPE             => 'sometimes|string|in:sorter,filter',
        Entity::GROUP            => 'sometimes|string|max:50',
        Entity::FILTER_TYPE      => 'sometimes|in:select,reject',
        Entity::METHOD           => 'sometimes|string',
        Entity::METHOD_TYPE      => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER => 'sometimes|string',
        Entity::NETWORK_CATEGORY => 'sometimes|string',
        Entity::SHARED_TERMINAL  => 'sometimes|boolean',
        Entity::NETWORK          => 'sometimes|string',
        Entity::INTERNATIONAL    => 'sometimes|boolean',
        Entity::ISSUER           => 'sometimes|string',
        Entity::MIN_AMOUNT       => 'sometimes|integer',
        Entity::MAX_AMOUNT       => 'sometimes|integer',
        Entity::EMI_DURATION     => 'sometimes|integer',
        Entity::EMI_SUBVENTION   => 'sometimees|string',
        Entity::CURRENCY         => 'sometimes|string',
        Entity::CATEGORY2        => 'sometimes|string',
    ];

    /**
     * Fetches rules matching a given search criteria
     * @param  array                    $criteria
     * @return Base\PublicCollection
     */
    public function fetchRulesForSearchCriteria(array $criteria): Base\PublicCollection
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $criteria);

        $rules = $query->get();

        return $rules;
    }

    /**
     * Adds where clauses to the select query depending on the type of values
     * - If the key has a custom function defined use that for getting the clause
     * - If the key is an array builds query like WHERE IN (<val1>, <val2>)
     * - If the key belongs to NULLABLE_ATTRIBUTES builds query like WHERE (key = val OR key IS NULL)
     *   This is required to handle cases where some rules can have null value for thse attributes
     *   signifying any/all hence we need to include these rules also
     * - Sample query below
     *   SELECT * FROM gateway_rules WHERE merchant_id IN (?, ?) AND gateway IN (?, ?, ?)
     *   AND method = ? AND (method_type = ? OR method_type IS NULL) AND (issuer = ? OR issuer IS NULL)
     *   AND (network = ? OR network IS NULL) AND (gateway_acquirer = ? OR gateway_acquirer IS NULL)
     *   AND international = false AND deleted_at IS NOT NULL
     *
     * @param  Querybuilder  $query  Query object
     * @param  array  $params query params
     */
    protected function buildSelectionQuery($query, array $params)
    {
        foreach ($params as $key => $value)
        {
            $this->addQueryForAttribute($query, $key, $params);
        }
    }

    protected function addQueryForAttribute($query, $key, $params)
    {
        if ($params[$key] !== null)
        {
            $query->where(function ($query) use ($key, $params)
            {
                $func = 'addQueryFor' . studly_case($key);

                if (method_exists($this, $func) === true)
                {
                    $this->$func($query, $params);
                }
                else if (is_array($params[$key]) === true)
                {
                    $query->whereIn($key, $params[$key]);
                }
                else
                {
                    $query->where($key, '=', $params[$key]);
                }
                // For some attributes in which null satisfies the selection
                // criteria add a clause like IR WHERE <key> IS NULL
                if (in_array($key, Entity::NULLABLE_ATTRIBUTES, true) === true)
                {
                    $query->orWhereNull($key);
                }
            });
        }
        else
        {
            if (in_array($key, Entity::NULLABLE_ATTRIBUTES, true) === true)
            {
                $query->whereNull($key);
            }
        }
    }

    /**
     * If ID is present in the search criteria,we fetch all other rules which dont have the ID
     * ID will be present only if an existing rule is being edited
     *
     * @param Querybuilder $query
     * @param array        $params
     */
    protected function addQueryForId($query, $params)
    {
        $query->where(Entity::ID, '!=', $params[Entity::ID]);
    }

    /**
     * We always check for filter_type not equal to that of current rule, so as
     * to find rules with matching criteria but opposite filter action
     */
    protected function addQueryForFilterType($query, $params)
    {
        $query->where(Entity::FILTER_TYPE, '!=', $params[Entity::FILTER_TYPE]);
    }

    /**
     * min_amount and max_amount are handled like below as they represent a range
     * and we want to find rules which overlap this range
     */
    protected function addQueryForMinAmount($query, $params)
    {
        if (isset($params[Entity::MAX_AMOUNT]) === true)
        {
            $query->where(Entity::MIN_AMOUNT, '<=', $params[Entity::MAX_AMOUNT]);
        }
    }

    protected function addQueryForMaxAmount($query, $params)
    {
        $query->where(Entity::MAX_AMOUNT, '>=', $params[Entity::MIN_AMOUNT]);
    }
}
