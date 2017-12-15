<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ORG_ID   => 'sometimes|string',
            Entity::HOSTNAME => 'sometimes|string|max:255',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ORG_ID,
            Entity::HOSTNAME,
        ],
    ];
}
