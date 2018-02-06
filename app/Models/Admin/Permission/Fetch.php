<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::CATEGORY  => 'sometimes|string|max:255',
            Entity::NAME      => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::CATEGORY,
            Entity::NAME,
        ],
    ];
}
