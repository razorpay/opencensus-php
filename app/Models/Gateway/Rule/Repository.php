<?php

namespace RZP\Models\Gateway\Rule;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_rule';

    protected $entityFetchParamRules = [
        Entity::GATEWAY          => 'sometimes|string|max:25',
        Entity::ORG_ID           => 'sometimes|alpha_num|size:14',
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
        Entity::RECURRING        => 'sometimes|boolean',
    ];

    /**
     * Fetches rules matching a given search criteria
     * @param  array                    $criteria
     * @return Base\PublicCollection
     */
    public function fetchRulesForSearchCriteria(array $criteria): Base\PublicCollection
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $criteria, Entity::NULLABLE_ATTRIBUTES);

        $rules = $query->get();

        return $rules;
    }

    public function fetchAuthenticationRulesForSearchCriteria(array $criteria): Base\PublicCollection
    {
        $query = $this->newQuery();

        $this->buildSelectionQuery($query, $criteria, Entity::AUTHENTICATION_NULLABLE_ATTRIBUTES);

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
     * @param  QueryBuilder  $query  Query object
     * @param  array         $params query params
     */
    protected function buildSelectionQuery(QueryBuilder $query, array $params, array $nullableParams = [])
    {
        foreach ($params as $key => $value)
        {
            $this->addQueryForAttribute($query, $key, $params, $nullableParams);
        }
    }

    /**
     * @param QueryBuilder $query
     * @param $key
     * @param $params
     */
    protected function addQueryForAttribute(QueryBuilder $query, string $key, array $params, array $nullableParams)
    {
        if ($params[$key] !== null)
        {
            $query->where(function ($query) use ($key, $params, $nullableParams)
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
                if (in_array($key, $nullableParams, true) === true)
                {
                    $query->orWhereNull($key);
                }
            });
        }
        else
        {
            if (in_array($key, $nullableParams, true) === true)
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
    protected function addQueryForId(QueryBuilder $query, array $params)
    {
        $query->where(Entity::ID, '!=', $params[Entity::ID]);
    }

    /**
     * For the merchant_id attribute below function covers the following two cases
     * - While adding / editing a rule if rule is for shared merchant, we search all
     *   applicable rules. If rule is not for shared merchant, we only check for rules
     *   which either belong to this merchant or the shared merchant
     * - While fetching rules applicable for payment, the merchant making the payment
     *   will never be for shared merchant, hence always fetching rules applicable
     *   for the payment merchant and the shared merchant
     *
     * @param Querybuilder $query  [description]
     *
     * @param array        $params [description]
     */
    protected function addQueryForMerchantId(Querybuilder $query, array $params)
    {
        if ($params[Entity::MERCHANT_ID] !== Account::SHARED_ACCOUNT)
        {
            $query->whereIn(Entity::MERCHANT_ID, [$params[Entity::MERCHANT_ID], Account::SHARED_ACCOUNT])
                  ->orWhereNull(Entity::MERCHANT_ID);
        }
    }

    /**
     * We always check for filter_type not equal to that of current rule, so as
     * to find rules with matching criteria but opposite filter action
     * @param QueryBuilder $query
     * @param array        $params
     */
    protected function addQueryForFilterType(QueryBuilder $query, array $params)
    {
        $query->where(Entity::FILTER_TYPE, '!=', $params[Entity::FILTER_TYPE]);
    }

    /**
     * min_amount and max_amount are handled like below as they represent a range
     * and we want to find rules which overlap this range
     * @param QueryBuilder $query
     * @param array        $params
     */
    protected function addQueryForMinAmount(QueryBuilder $query, array $params)
    {
        if (isset($params[Entity::MAX_AMOUNT]) === true)
        {
            $query->where(Entity::MIN_AMOUNT, '<=', $params[Entity::MAX_AMOUNT]);
        }
    }

    /**
     * @param QueryBuilder $query
     * @param array $params
     */
    protected function addQueryForMaxAmount(QueryBuilder $query, array $params)
    {
        $query->where(Entity::MAX_AMOUNT, '>=', $params[Entity::MIN_AMOUNT]);
    }

    protected function addQueryAuthType(Querybuilder $query, array $params)
    {
        $query->whereIn(Entity::AUTH_TYPE, $params[Entity::AUTH_TYPE]);
    }
}
