<?php

namespace RZP\Models\Admin\Org;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::EMAIL                 => 'sometimes|email',
            Entity::AUTH_TYPE             => 'sometimes|string|max:50',
            Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
            Entity::ALLOW_SIGN_UP         => 'sometimes|boolean',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::EMAIL,
            Entity::AUTH_TYPE,
            Entity::EMAIL_DOMAINS,
            Entity::ALLOW_SIGN_UP,
        ],
    ];
}
