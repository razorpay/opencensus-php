<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::GATEWAY  => 'sometimes|string|max:255',
            Entity::ISSUER   => 'sometimes|string|max:50',
            Entity::ACQUIRER => 'sometimes|string|max:30',
            Entity::METHOD   => 'sometimes|string|max:30',
            Entity::BEGIN    => 'sometimes|integer',
            Entity::END      => 'sometimes|integer',
            Entity::PARTIAL  => 'sometimes|bool',
            Entity::SOURCE   => 'sometimes|string|max:30',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::GATEWAY,
            Entity::ISSUER,
            Entity::METHOD,
            Entity::BEGIN,
            Entity::END,
            Entity::PARTIAL,
            Entity::SOURCE,
        ],
    ];
}
