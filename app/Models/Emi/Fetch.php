<?php

namespace RZP\Models\Emi;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BANK            => 'sometimes|string|size:4',
            Entity::NETWORK         => 'sometimes|string|max:12',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::BANK,
            Entity::NETWORK,
        ]
    ];
}
