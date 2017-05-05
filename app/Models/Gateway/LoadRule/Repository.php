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

    public function getTotalLoadForSimilarRules(Entity $rule)
    {
        $params = [];

        $input = $rule->toArray();

        foreach (Entity::QUERY_ATTRIBUTES as $key)
        {
            $params[$key] = $input[$key] ?? null;
        }

        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $params);

        if ($rule->exists === true)
        {
            $query->where(Entity::ID, '!=', $rule->getId());
        }

        return $query->sum(Entity::LOAD);
    }

    public function fetchApplicableRules(array $ruleFetchParams)
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $ruleFetchParams);

        return $query->get();
    }

    /**
     * Adds where clauses to the select query depending on the type of keys
     * - If the key is an array builds query like WHERE IN (<val1>, <val2>)
     * - If the key belongs to NULLABLE_ATTRIBUTES builds query like WHERE (key = val OR key IS NULL)
     *   This is required to handle cases where some rules can have null value for thse attributes
     *   signifying any/all hence we need to include these rules also
     * - For attributes which are not in NULLABLE_ATTRIBUTES we add clause like
     *   WHERE key = <value>, or WHERE <key> is NULL if the value is null
     * TODO: add sample query covering all above cases
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
            else if ((in_array($key, Entity::NULLABLE_ATTRIBUTES, true) === true))
            {
                $this->addQueryForNullableAttribute($query, $key, $params);
            }
            else
            {
                $this->addQueryForNonNullableAttribute($query, $key, $params);
            }
        }
    }

    protected function addQueryForNullableAttribute($query, $key, $params)
    {
        $value = $params[$key];

        $query->where(function ($query) use ($key, $value)
        {
            if ($value !== null)
            {
                $query->where($key, '=', $value)
                      ->orWhereNull($key);
            }
            // else
            // {
            //     $query->whereNull($key);
            // }
        });
    }

    protected function addQueryForNonNullableAttribute($query, $key, $params)
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

    /**
     * Special handling for intternational attribute, as it is a boolean, so if it
     * is not present or null we want to cast it to bool and then add query
     * @param $query  Selection query
     * @param array $params query params
     */
    protected function addQueryParamInternational($query, $params)
    {
        $international = (bool) $params[Entity::INTERNATIONAL];

        $query->where(Entity::INTERNATIONAL, '=', $international);
    }
}
