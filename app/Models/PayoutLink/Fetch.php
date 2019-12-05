<?php

namespace RZP\Models\PayoutLink;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;


class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID => 'sometimes|public_id|size:19',
        ],
        AuthType::PROXY_AUTH => [
            Entity::ID          => 'sometimes|public_id|size:19',
            Entity::CONTACT_ID => 'sometimes|public_id|size:19'
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::ID => 'sometimes|public_id|size:19',
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::ID          => 'sometimes|public_id|size:19',
            Entity::CONTACT_ID => 'sometimes|public_id|size:19'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::CONTACT_ID
        ],
        AuthType::PROXY_AUTH     => [
            Entity::ID,
            Entity::CONTACT_ID
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::ID
        ],
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];

    const ES_FIELDS = [
    ];

    const COMMON_FIELDS = [
        Entity::ID,
    ];
}
