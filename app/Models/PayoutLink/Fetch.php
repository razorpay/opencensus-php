<?php

namespace RZP\Models\PayoutLink;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS         => [
            Entity::ID         => 'sometimes|public_id|size:19',
            Entity::CONTACT_ID => 'sometimes|public_id|size:19'
        ],
        AuthType::PROXY_AUTH => [
            Entity::FUND_ACCOUNT_ID => 'filled|string|public_id'
        ]
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ID,
            Entity::CONTACT_ID
        ],
        AuthType::PROXY_AUTH => [
            Entity::FUND_ACCOUNT_ID
        ]
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];
}
