<?php

namespace RZP\Models\RoleAccessPolicyMap;

use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ROLE_ID                   => 'sometimes|max:19',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ROLE_ID,
            Entity::ACCESS_POLICY_IDS,
        ],
    ];
}
