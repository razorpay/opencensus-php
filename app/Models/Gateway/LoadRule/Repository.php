<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_load_rule';

    protected $entityFetchParamRules = [
        Entity::GATEWAY          => 'sometimes|string|max:25',
        Entity::MERCHANT_ID      => 'sometimes|alpha_num|size:14',
        Entity::METHOD           => 'sometimes|string',
        Entity::CARD_TYPE        => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER => 'sometimes|string',
        Entity::NETWORK          => 'sometimes|string',
        Entity::INTERNATIONAL    => 'sometimes|boolean',
        Entity::ISSUER           => 'sometimes|string',
    ];

    public function findExistingRule(array $input)
    {
        return $this->fetch($input)->first();
    }

    public function fetchConflictingRules(array $input)
    {
        $params = [];

        foreach (Entity::QUERY_ATTRIBUTES as $key)
        {
            if (empty($input[$key]) === false)
            {
                $params[$key] = $input[$key];
            }
        }

        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $params);

        return $query->get();
    }

    public function fetchApplicableRules(array $ruleFetchParams)
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $ruleFetchParams);

        return $query->get();
    }

    /**
     * Adds where clauses to the select query depending on the type of keys
     * If the key is an array builds query like WHERE IN (<val1>, <val2>)
     * If the key belongs to NULLABLE_ATTRIBUTES builds query like WHERE (key = val OR key IS NULL)
     * This is required to handle cases where some rules can have null value for thse attributes
     * signifying any/all hence we need to include these rules also
     * In all other cases just adds simple where clause like WHERE key = valie
     *
     * @param  Querybuilder  $query  Query object
     * @param  array  $params query params
     */
    protected function buildSelectionQuery($query, array $params)
    {
        foreach ($params as $key => $value)
        {
            if (is_array($value) === true)
            {
                $query->whereIn($key, $value);
            }
            else if (in_array($key, Entity::NULLABLE_ATTRIBUTES, true) === true)
            {
                $query->where(function ($query) use ($key, $value)
                {
                    $query->where($key, '=', $value)
                          ->orWhereNull($key);
                });
            }
            else
            {
                $query->where($key, '=', $value);
            }
        }
    }
}
