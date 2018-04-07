<?php

namespace RZP\Models\Report;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID     => 'sometimes|alpha_dash',
            Entity::TYPE            => 'sometimes|string'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
        ]
    ];
}
