<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID => 'sometimes|string',
            Entity::STATUS                          => 'sometimes|string'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID,
            Entity::STATUS
        ],
    ];
}
