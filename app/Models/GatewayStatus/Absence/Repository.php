<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_absence';

    // These are proxy allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::BANK           => 'sometimes|string|max:255',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer'
    );
}
