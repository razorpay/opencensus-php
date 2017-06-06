<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_downtime';

    protected $entityFetchParamRules = array(
        Entity::GATEWAY     => 'sometimes|string|max:255',
        Entity::ISSUER      => 'sometimes|string|max:50',
        Entity::METHOD      => 'sometimes|string|max:30',
        Entity::BEGIN       => 'sometimes|integer',
        Entity::END         => 'sometimes|integer',
        Entity::PARTIAL     => 'sometimes|bool',
        Entity::SOURCE      => 'sometimes|string|max:30',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY     => 'sometimes|string|max:255',
        Entity::ISSUER      => 'sometimes|string|max:50',
        Entity::METHOD      => 'sometimes|string|max:30',
        Entity::BEGIN       => 'sometimes|integer',
        Entity::END         => 'sometimes|integer',
        Entity::PARTIAL     => 'sometimes|bool',
        Entity::SOURCE      => 'sometimes|string|max:30',
    );

    const KEY_OPERATOR_MAP = [
        Entity::GATEWAY => '=',
        Entity::ISSUER  => '=',
        Entity::METHOD  => '=',
        Entity::SOURCE  => '=',
        Entity::BEGIN   => '<='
    ];

    const UNIQUE_KEYS = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::BEGIN,
    ];

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchUnique($input)
    {
        $params = [];

        foreach (self::UNIQUE_KEYS as $key)
        {
            if (isset($input[$key]) === true)
            {
                $params[$key] = $input[$key];
            }
        }

        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP, $params, $query);

        return $query->whereNull(Entity::END)
                     ->orderBy(Entity::CREATED_AT)
                     ->first();
    }

    public function fetchMostRecentActive(array $input)
    {
        $params = [];

        foreach (self::UNIQUE_KEYS as $key)
        {
            if (isset($input[$key]) === true)
            {
                $params[$key] = $input[$key];
            }
        }

        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP, $input, $query);

        return $query->whereNull(Entity::END)
                     ->where(Entity::SCHEDULED, '=', false)
                     ->latest()
                     ->first();
    }

    public function fetchDowntimesWithoutTerminal(array $input)
    {
        $query = $this->newQuery();

        $this->buildFetchQuery($query, $input);

        return $query->whereNull(Entity::TERMINAL_ID)
                     ->get();
    }

    /**
     * Fetches downtimes for DonwtimeSorter
     *
     * Params are provided as [key => val]
     * where 'val' can either be an array or string
     *
     * $timestamp is provided separately because
     * it has to be compared to 'begin' & 'end'
     *
     * @param $params array
     * @return collection
     */
    public function fetchApplicableDowntimesForPayment(array $params)
    {
        $query = $this->newQuery();

        $this->buildFetchQuery($query, $params);

        return $query->get();
    }

    /**
     * Helper function to add param valus to the query
     *
     * Overrides the default `addQueryParamDefault` in Repository Fetch
     * because we need to handle the case where the comparison could be
     * on an array of values, e.g. : terminal gateways, networks etc.
     *
     * @param $query \RZP\Base\BuilderEx
     * @param $params array
     * @param $key string
     */
    protected function addQueryParamDefault($query, $params, $key)
    {
        $value = $params[$key];

        if ($value === 'null')
        {
            $query->whereNull($key);
        }
        else if (is_array($value) === true)
        {
            $query->whereIn($key, $value);
        }
        else
        {
            $query->where($key, '=', $value);
        }
    }

    protected function buildQuery(
        array $keyOperatorMap, array $input, \RZP\Base\BuilderEx & $query)
    {
        foreach ($keyOperatorMap as $key => $operator)
        {
            if (isset($input[$key]) === true)
            {
                $query->where($key, $operator , $input[$key]);
            }
        }
    }

    protected function addQueryParamBegin($query, $params)
    {
        // The default value for Entity::END is null. This is because we do not
        // necessarily know the end time in case of an unscheduled downtime.
        //
        // If an end time does exist, downtime should have ended after
        // the start of the query begin time for there to be an overlap
        $query->whereNull(Entity::END)
              ->orWhere(Entity::END, '>=', $params[Entity::BEGIN]);
    }

    protected function addQueryParamEnd($query, $params)
    {
        // If query does have an endtime, then downtime should
        // have begun before it for there to be an overlap
        $query->where(Entity::BEGIN, '<=', $params[Entity::END]);
    }
}
