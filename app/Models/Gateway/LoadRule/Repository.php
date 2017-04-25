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
        Entity::LOAD             => 'sometimes|integer|min:0|max:10000'
    ];

    /**
     * Defines a map of keys on which to query for conflicting rules. The boolean
     * value determines if we additionally need to check for null in the query
     */
    const QUERY_KEYS = [
        Entity::MERCHANT_ID   => false,
        Entity::METHOD        => false,
        Entity::CARD_TYPE     => true,
        Entity::NETWORK       => true,
        Entity::ISSUER        => true,
        Entity::INTERNATIONAL => false
    ];

    public function findExistingRule(array $input)
    {
        return $this->fetch($input)->first();
    }

    public function fetchConflictingRules(array $input)
    {
        $params = [];

        // For certain keys like network, issuer we also include null as a search attribute
        // as it signifies any / all value for the attribute. For e.g 'null' network
        // means that rule is applicable for all networks. So a rule with network VISA, may
        // potentially conflict with this rule. So we form a query like
        // WHERE <attribute> IN (null, <value>)
        foreach (self::QUERY_KEYS as $key => $includeNull)
        {
            if (empty($input[$key]) === false)
            {
                $params[$key] = $input[$key];

                if ($includeNull === true)
                {
                    $params[$key] = [$input[$key], null];
                }
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
                $query->where($key, '=', $value);
            }
        }
    }
}
