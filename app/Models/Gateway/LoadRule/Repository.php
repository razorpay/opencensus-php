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
        Entity::CATEGORY         => 'sometimes|string|max:4',
        Entity::GATEWAY_ACQUIRER => 'sometimes|string',
        Entity::NETWORK          => 'sometimes|string',
        Entity::INTERNATIONAL    => 'sometimes|boolean',
        Entity::ISSUER           => 'sometimes|string',
        Entity::LOAD             => 'sometimes|integer|min:0|max:10000'
    ];

    const MATCH_KEYS = [
        Entity::MERCHANT_ID,
        Entity::METHOD,
        Entity::CARD_TYPE,
        Entity::CATEGORY,
        Entity::NETWORK,
        Entity::INTERNATIONAL,
        Entity::ISSUER,
    ];

    public function findExistingRule(array $input)
    {
        return $this->fetch($input)->first();
    }

    public function fetchMatchingRules(array $input)
    {
        $params = [];

        foreach (self::MATCH_KEYS as $key)
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
