<?php

namespace RZP\Models\CreditNote;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::SUBSCRIPTION_ID => 'sometimes|string|min:14|max:18',
            Entity::STATUS          => 'sometimes|sequential_array',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::SUBSCRIPTION_ID,
            Entity::STATUS,
        ],
    ];

    const SIGNED_IDS = [
        Entity::SUBSCRIPTION_ID,
    ];
}
