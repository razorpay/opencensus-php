<?php

namespace RZP\Models\BharatQr;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PAYMENT_ID            => 'sometimes|string|min:14|max:18',
            Entity::VIRTUAL_ACCOUNT_ID    => 'sometimes|string|min:14|max:17',
            Entity::METHOD                => 'sometimes|string|in:card,upi',
            Entity::PROVIDER_REFERENCE_ID => 'sometimes|string',
            Entity::MERCHANT_REFERENCE    => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::PAYMENT_ID,
            Entity::VIRTUAL_ACCOUNT_ID,
            Entity::METHOD,
            Entity::PROVIDER_REFERENCE_ID,
            Entity::MERCHANT_REFERENCE,
        ],
    ];

    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
    ];

    const COMMON_FIELDS = [
        Entity::PAYMENT_ID,
        Entity::METHOD,
    ];
}
