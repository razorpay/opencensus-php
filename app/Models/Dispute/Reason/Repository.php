<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'dispute_reason';

    // These are merchant allowed params to search on. These also act as default params.
    protected $appFetchParamRules = [
        Entity::NETWORK             => 'sometimes|string|custom',
        Entity::GATEWAY_CODE        => 'sometimes|string',
        Entity::CODE                => 'sometimes|string',
        Entity::DESCRIPTION         => 'sometimes|string',
        Entity::GATEWAY_DESCRIPTION => 'sometimes|string',
    ];

    protected function validateNetwork($attribute, $value)
    {
        (new Validator)->validateNetwork($attribute, $value);
    }
}
