<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_absence';

    // These are proxy allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::ISSUER         => 'sometimes|string|max:255',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer',
        Entity::PARTIAL        => 'sometimes|bool',
    );

    public function fetchAbsent($input)
    {
        $query = $this->newQuery();

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

        if (isset($input[Entity::TO]))
        {
            $query->where(Entity::TO, '>=', $input[Entity::TO]);
        }

        return $query->get();
    }
}
