<?php

namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    public const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID          => 'sometimes|string',
        ],
    ];

    public const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
    ];
}
