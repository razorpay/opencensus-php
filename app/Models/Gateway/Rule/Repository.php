<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_rule';

    /**
     * Attributes used for fetching rules matching these keys from database
     */
    const QUERY_ATTRIBUTES = [
        Entity::MERCHANT_ID,
        Entity::METHOD,
        Entity::METHOD_TYPE,
        Entity::NETWORK,
        Entity::ISSUER,
        Entity::INTERNATIONAL,
    ];

    protected $entityFetchParamRules = [
        Entity::GATEWAY          => 'sometimes|string|max:25',
        Entity::MERCHANT_ID      => 'sometimes|alpha_num|size:14',
        Entity::METHOD           => 'sometimes|string',
        Entity::METHOD_TYPE      => 'sometimes|string',
        Entity::GATEWAY_ACQUIRER => 'sometimes|string',
        Entity::NETWORK          => 'sometimes|string',
        Entity::INTERNATIONAL    => 'sometimes|boolean',
        Entity::ISSUER           => 'sometimes|string',
    ];

    /**
     * If we have rule R1 for gateway A with network null, and we are defining new rule R2
     * for gateway B with network VISA. For a VISA payment both rules R1 and R2 will
     * be applicable, i.e rule R1's criteria satisfies R2's criteria.
     *
     * This method computes the total load across all such rules which match the
     * new rule's criteria
     *
     * @param  Entity $rule New rule entity
     * @return int          Total load across matching rules
     */
    public function getTotalLoadForRulesWithMatchingCriteria(Entity $rule): int
    {
        $params = [];

        $input = $rule->toArray();

        foreach (self::QUERY_ATTRIBUTES as $key)
        {
            $params[$key] = $input[$key] ?? null;
        }

        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $params);

        // If the rule against which we are matching is an existing rule, we exclude
        // it in the query
        if ($rule->exists === true)
        {
            $query->where(Entity::ID, '!=', $rule->getId());
        }

        return (int) $query->sum(Entity::LOAD);
    }

    /**
     * Fetches rules for terminal selection as per the parameters provided
     *
     * @param  array  $params Query parameter values
     */
    public function fetchApplicableRulesForPayment(array $params): Base\PublicCollection
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $params);

        return $query->get();
    }

    /**
     * Adds where clauses to the select query depending on the type of keys
     * - If the key is an array builds query like WHERE IN (<val1>, <val2>)
     * - If the key belongs to NULLABLE_ATTRIBUTES builds query like WHERE (key = val OR key IS NULL)
     *   This is required to handle cases where some rules can have null value for thse attributes
     *   signifying any/all hence we need to include these rules also
     * - Sample query below
     *   SELECT * FROM load_rules WHERE merchant_id IN (?, ?) AND gateway IN (?, ?, ?)
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
            if (is_array($value) === true)
            {
                $query->whereIn($key, $value);
            }
            else
            {
                $this->addQueryForAttribute($query, $key, $params);
            }
        }
    }

    protected function addQueryForAttribute($query, $key, $params)
    {
        $value = $params[$key];

        $query->where(function ($query) use ($key, $value)
        {
            if ($value !== null)
            {
                $query->where($key, '=', $value);

                // For some attributes in which null satisfies the selection
                // criteria add a clause like IR WHERE <key> IS NULL
                if (in_array($key, Entity::NULLABLE_ATTRIBUTES, true) === true)
                {
                    $query->orWhereNull($key);
                }
            }
        });
    }
}
