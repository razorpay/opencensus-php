<?php

namespace RZP\Models\Settlement\Ondemand;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                    => 'sometimes|public_id|size:21',
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
            Entity::STATUS                => 'sometimes|string',
        ],
        AuthType::PRIVATE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:settlement_ondemand_payouts',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PRIVATE_AUTH => [
            self::EXPAND_EACH,
            Entity::STATUS,
        ],
        AuthType::PROXY_AUTH => [
        ]
    ];
}
