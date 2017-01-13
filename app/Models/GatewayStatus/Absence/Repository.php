<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_absence';

    protected $entityFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::ISSUER         => 'sometimes|string|max:50',
        Entity::METHOD         => 'sometimes|string|max:30',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer',
        Entity::PARTIAL        => 'sometimes|bool',
        Entity::SOURCE         => 'sometimes|string|max:30'
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::ISSUER         => 'sometimes|string|max:50',
        Entity::METHOD         => 'sometimes|string|max:30',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer',
        Entity::PARTIAL        => 'sometimes|bool',
        Entity::SOURCE         => 'sometimes|string|max:30'
    );

    const KEY_OPERATOR_MAP = [
        Entity::GATEWAY => '=',
        Entity::ISSUER => '=',
        Entity::METHOD => '=',
        Entity::SOURCE => '=',
        Entity::FROM => '<='
    ];
    
    /**
     * We are using a custom fetch function here since we do not want to override fetch function.
     * @param array $input
     * @return mixed
     */
    public function fetchAbsent(array $input)
    {
        $query = $this->newQuery();

        // The default value for Entity::TO is null. This is because we do not necessarily know
        // the end time in case of an unscheduled downtime. So, for all these scenarios, we are
        // setting the $to value to $input['to'] if available or $input['from']. The essential
        // idea is to fetch the list of gateways/issuers at the current point in time.
        $to = (isset($input[Entity::TO]) === true) ?? null;

        if ((empty($to) === true) and (isset($input[Entity::FROM])))
        {
            $to = $input[Entity::FROM];
        }

        $this->buildQuery(self::KEY_OPERATOR_MAP, $input, $query);

        if (empty($to) === false)
        {
            $query->where(function ($query) use ($to)
            {
                $query->whereNull(Entity::TO);
                $query->orWhere(Entity::TO, '>=', $to);
            });
        }

        return $query->get();
    }

    public function fetchMostRecentActive(array $input)
    {
        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP, $input, $query);

        $query->whereNull(Entity::TO);

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
}
