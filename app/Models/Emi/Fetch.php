<?php

namespace RZP\Models\Emi;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BANK               => 'sometimes|string|size:4',
            Entity::NETWORK            => 'sometimes|string|max:12',
            Entity::COBRANDING_PARTNER => 'sometimes|string',
            Entity::MERCHANT_ID        => 'sometimes|string|size:14',
            Entity::DURATION           => 'sometimes|integer',
            Entity::TYPE               => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::BANK,
            Entity::NETWORK,
            Entity::COBRANDING_PARTNER,
            Entity::DURATION,
            Entity::MERCHANT_ID,
            Entity::TYPE,
        ]
    ];
}
