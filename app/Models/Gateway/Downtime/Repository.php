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
        Entity::SOURCE      => 'sometimes|string|max:30'
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY     => 'sometimes|string|max:255',
        Entity::ISSUER      => 'sometimes|string|max:50',
        Entity::METHOD      => 'sometimes|string|max:30',
        Entity::BEGIN       => 'sometimes|integer',
        Entity::END         => 'sometimes|integer',
        Entity::PARTIAL     => 'sometimes|bool',
        Entity::SOURCE      => 'sometimes|string|max:30'
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

        return $query->orderBy(Entity::CREATED_AT)->first();
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

        $query->whereNull(Entity::END);

        return $query->latest();
    }

    protected function buildQuery(array $keyOperatorMap, array $input, \RZP\Base\BuilderEx & $query)
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
        $query->where(Entity::BEGIN, '<=', $params[Entity::BEGIN]);
    }

    protected function addQueryParamEnd($query, $params)
    {
        // The default value for Entity::END is null. This is because we do not necessarily know
        // the end time in case of an unscheduled downtime. So, for all these scenarios, we are
        // setting the $to value to $input['to'] if available or $input['from']. The essential
        // idea is to fetch the list of gateways/issuers at the current point in time.
        $to = $params[Entity::END] ?? null;

        if ((empty($to) === true) and
            (isset($params[Entity::BEGIN])))
        {
            $to = $params[Entity::BEGIN];
        }

        if (empty($to) === false)
        {
            $query->where(function ($query) use ($to)
            {
                $query->whereNull(Entity::END);
                $query->orWhere(Entity::END, '>=', $to);
            });
        }
    }
}
