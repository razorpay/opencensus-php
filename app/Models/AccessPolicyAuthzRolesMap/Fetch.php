<?php
namespace RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Constants;
use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PRIVILEGE_ID          => 'sometimes||string|size:14',
            Entity::ACTION                => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::PRIVILEGE_ID,
            Entity::ACTION,
        ],
    ];
}
