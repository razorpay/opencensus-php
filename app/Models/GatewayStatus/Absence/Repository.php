<?php

namespace RZP\Models\GatewayStatus\Absence;

use DB;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_absence';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::ISSUER         => 'sometimes|string|max:255',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer',
        Entity::PARTIAL        => 'sometimes|bool',
    );


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
        $to = (isset($input[Entity::TO]) === true) ? $input[Entity::TO] : null;

        if ((empty($to) === true) and (isset($input[Entity::FROM])))
        {
            $to = $input[Entity::FROM];
        }

        if (isset($input[Entity::GATEWAY]))
        {
            $query->where(Entity::GATEWAY, '=', $input[Entity::GATEWAY]);
        }

        if (isset($input[Entity::ISSUER]))
        {
            $query->where(Entity::ISSUER, '=', $input[Entity::ISSUER]);
        }

        if (isset($input[Entity::FROM]))
        {
            $query->where(Entity::FROM, '<=', $input[Entity::FROM]);
        }

        if (empty($to) === false)
        {
            $query->where(function ($query) use ($to)
            {
                $query->whereNull(Entity::TO);
                $query->orwhere(Entity::TO, '>=', $to);
            });
        }

        return $query->get();
    }
}
