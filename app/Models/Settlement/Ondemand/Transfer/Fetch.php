<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PAYOUT_ID   => 'sometimes|string',
            Entity::STATUS      => 'sometimes|string',
            Entity::MODE        => 'sometimes|string'
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::PAYOUT_ID,
            Entity::STATUS,
            Entity::MODE
        ],
    ];
}
